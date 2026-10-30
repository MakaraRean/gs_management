<?php

namespace Tests\Feature;

use App\Enums\DeletionRequestStatus;
use App\Enums\Role;
use App\Models\Business;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DeletionRequest;
use App\Models\Sale;
use App\Models\Station;
use App\Models\User;
use ArrayObject;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: Role}>
     */
    public static function staffRoleProvider(): array
    {
        return [
            'cashier' => [Role::Cashier],
            'gas jockey' => [Role::GasJockey],
        ];
    }

    public function test_gas_jockey_can_request_a_sale_deletion_once(): void
    {
        [$gasJockey, $station] = $this->memberWithStation(Role::GasJockey);
        $sale = Sale::factory()->create(['station_id' => $station->id]);

        foreach (['Wrong pump', 'Still the wrong pump'] as $reason) {
            $this->actingAs($gasJockey)
                ->withUnencryptedCookie('station_id', $station->id)
                ->from(route('sales.index'))
                ->post(route('sales.deletion-request.store', $sale), ['reason' => $reason])
                ->assertRedirect(route('sales.index'));
        }

        $this->assertRecordActive($sale);
        $this->assertDatabaseCount('deletion_requests', 1);
        $this->assertDatabaseHas('deletion_requests', [
            'business_id' => $station->business_id,
            'station_id' => $station->id,
            'deletable_type' => 'sale',
            'deletable_id' => $sale->id,
            'requested_by' => $gasJockey->id,
            'reason' => 'Wrong pump',
            'status' => 'pending',
        ]);
    }

    public function test_requesting_a_deletion_requires_a_reason(): void
    {
        [$cashier, $station] = $this->memberWithStation(Role::Cashier);
        $entry = CashFlow::factory()->create(['station_id' => $station->id]);

        $this->actingAs($cashier)
            ->withUnencryptedCookie('station_id', $station->id)
            ->from(route('cash-flow.index'))
            ->post(route('cash-flow.deletion-request.store', $entry), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('deletion_requests', 0);
    }

    #[DataProvider('staffRoleProvider')]
    public function test_staff_cannot_delete_records_directly(Role $role): void
    {
        [$member, $station] = $this->memberWithStation($role);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $entry = CashFlow::factory()->create(['station_id' => $station->id]);
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);
        $payment = CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $station->id]);

        $this->actingAs($member)->withUnencryptedCookie('station_id', $station->id);

        $this->delete(route('sales.destroy', $sale))->assertForbidden();
        $this->delete(route('cash-flow.destroy', $entry))->assertForbidden();
        $this->delete(route('customers.destroy', $customer))->assertForbidden();
        $this->delete(route('customers.payments.destroy', [$customer, $payment]))->assertForbidden();

        foreach ([$sale, $entry, $customer, $payment] as $record) {
            $this->assertRecordActive($record);
        }
    }

    public function test_gas_jockey_cannot_request_a_customer_deletion(): void
    {
        [$gasJockey, $station] = $this->memberWithStation(Role::GasJockey);
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);

        $this->actingAs($gasJockey)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('customers.deletion-request.store', $customer), ['reason' => 'Duplicate'])
            ->assertForbidden();

        $this->assertDatabaseCount('deletion_requests', 0);
    }

    public function test_records_outside_the_members_station_or_business_cannot_be_requested(): void
    {
        [$cashier, $station] = $this->memberWithStation(Role::Cashier);
        $otherStationSale = Sale::factory()->create([
            'station_id' => Station::factory()->create(['business_id' => $station->business_id])->id,
        ]);
        $foreignCustomer = Customer::factory()->create();
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);
        $otherCustomersPayment = CustomerPayment::factory()->create(['station_id' => $station->id]);

        $this->actingAs($cashier)->withUnencryptedCookie('station_id', $station->id);

        $this->post(route('sales.deletion-request.store', $otherStationSale), ['reason' => 'Mine?'])
            ->assertForbidden();
        $this->post(route('customers.deletion-request.store', $foreignCustomer), ['reason' => 'Mine?'])
            ->assertForbidden();
        $this->post(route('customers.payments.deletion-request.store', [$customer, $otherCustomersPayment]), ['reason' => 'Mine?'])
            ->assertNotFound();

        $this->assertDatabaseCount('deletion_requests', 0);
    }

    public function test_admins_see_their_business_pending_requests(): void
    {
        [$gasJockey, $station, $owner] = $this->memberWithStation(Role::GasJockey);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $sale->requestDeletion($gasJockey, 'Duplicate entry', $station->business, $station);
        DeletionRequest::factory()->rejected()->create(['business_id' => $station->business_id]);
        DeletionRequest::factory()->create();

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('deletion-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('deletion-requests/index')
                ->has('requests', 1)
                ->where('requests.0.type', 'sale')
                ->where('requests.0.amount', (float) $sale->total_amount)
                ->where('requests.0.station', $station->name)
                ->where('requests.0.requested_by', $gasJockey->name)
                ->where('requests.0.reason', 'Duplicate entry')
                ->where('pending_deletion_requests', 1));
    }

    #[DataProvider('staffRoleProvider')]
    public function test_staff_cannot_review_requests(Role $role): void
    {
        [$member, $station] = $this->memberWithStation($role);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $deletionRequest = $sale->requestDeletion($member, 'Mistake', $station->business, $station);

        $this->actingAs($member)->withUnencryptedCookie('station_id', $station->id);

        $this->get(route('deletion-requests.index'))->assertForbidden();
        $this->post(route('deletion-requests.approve', $deletionRequest))->assertForbidden();
        $this->post(route('deletion-requests.reject', $deletionRequest))->assertForbidden();

        $this->assertRecordActive($sale);
        $this->assertTrue($deletionRequest->fresh()->isPending());
    }

    public function test_approving_a_request_deletes_the_record(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $entry = CashFlow::factory()->create(['station_id' => $station->id]);
        $deletionRequest = $entry->requestDeletion($cashier, 'Typo in amount', $station->business, $station);

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('deletion-requests.approve', $deletionRequest))
            ->assertRedirect(route('deletion-requests.index'));

        $this->assertRecordDeactivated($entry);

        $deletionRequest->refresh();
        $this->assertSame(DeletionRequestStatus::Approved, $deletionRequest->status);
        $this->assertSame($owner->id, $deletionRequest->reviewed_by);
        $this->assertNotNull($deletionRequest->reviewed_at);
    }

    public function test_rejecting_a_request_keeps_the_record(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);
        $deletionRequest = $customer->requestDeletion($cashier, 'Duplicate customer', $station->business, $station);

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('deletion-requests.reject', $deletionRequest))
            ->assertRedirect(route('deletion-requests.index'));

        $this->assertRecordActive($customer);

        $deletionRequest->refresh();
        $this->assertSame(DeletionRequestStatus::Rejected, $deletionRequest->status);
        $this->assertSame($owner->id, $deletionRequest->reviewed_by);
    }

    public function test_requests_cannot_be_reviewed_by_another_business_or_twice(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        [, $otherStation, $otherOwner] = $this->memberWithStation(Role::Cashier);
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);
        $deletionRequest = $customer->requestDeletion($cashier, 'Duplicate customer', $station->business, $station);

        $this->actingAs($otherOwner)->withUnencryptedCookie('station_id', $otherStation->id);
        $this->post(route('deletion-requests.approve', $deletionRequest))->assertForbidden();
        $this->post(route('deletion-requests.reject', $deletionRequest))->assertForbidden();

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('deletion-requests.reject', $deletionRequest))
            ->assertRedirect(route('deletion-requests.index'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('deletion-requests.approve', $deletionRequest))
            ->assertRedirect(route('deletion-requests.index'))
            ->assertInertiaFlash('toast.type', 'info');

        $this->assertRecordActive($customer);
        $this->assertSame(DeletionRequestStatus::Rejected, $deletionRequest->fresh()->status);
    }

    public function test_a_stale_approval_cannot_override_a_rejection(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $deletionRequest = $sale->requestDeletion($cashier, 'Duplicate sale', $station->business, $station);
        $loadedByAnotherAdmin = DeletionRequest::query()->findOrFail($deletionRequest->id);

        $this->assertTrue($deletionRequest->reject($owner));
        $this->assertFalse($loadedByAnotherAdmin->approve($owner));
        $this->assertFalse($loadedByAnotherAdmin->reject($owner));

        $this->assertRecordActive($sale);
        $this->assertSame(DeletionRequestStatus::Rejected, $deletionRequest->fresh()->status);
    }

    public function test_a_record_deleted_in_the_meantime_cannot_be_requested(): void
    {
        [$cashier, $station] = $this->memberWithStation(Role::Cashier);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $loadedBeforeDeletion = Sale::query()->findOrFail($sale->id);
        $sale->deactivate();

        $this->assertThrows(
            fn () => $loadedBeforeDeletion->requestDeletion($cashier, 'Too late', $station->business, $station),
            NotFoundHttpException::class,
        );

        $this->assertDatabaseCount('deletion_requests', 0);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function recordTypeProvider(): array
    {
        return [
            'sale' => ['sale'],
            'cash flow entry' => ['cash_flow'],
            'customer' => ['customer'],
            'customer payment' => ['customer_payment'],
        ];
    }

    #[DataProvider('recordTypeProvider')]
    public function test_each_record_type_can_be_requested_and_approved(string $type): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        [$record, $requestUrl] = $this->requestableRecord($type, $station);

        $this->actingAs($cashier)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post($requestUrl, ['reason' => 'Entered by mistake'])
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success');

        $deletionRequest = DeletionRequest::query()->sole();
        $this->assertSame($type, $deletionRequest->deletable_type);
        $this->assertTrue($record->is($deletionRequest->deletable));

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('deletion-requests.index'))
            ->assertInertia(fn ($page) => $page
                ->has('requests', 1)
                ->where('requests.0.type', $type)
                ->where('requests.0.label', fn (?string $label) => filled($label)));

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('deletion-requests.approve', $deletionRequest))
            ->assertRedirect(route('deletion-requests.index'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertRecordDeactivated($record);
        $this->assertSame(DeletionRequestStatus::Approved, $deletionRequest->fresh()->status);
    }

    #[DataProvider('recordTypeProvider')]
    public function test_each_record_type_is_kept_when_its_request_is_rejected(string $type): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        [$record, $requestUrl] = $this->requestableRecord($type, $station);

        $this->actingAs($cashier)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post($requestUrl, ['reason' => 'Entered by mistake'])
            ->assertRedirect();

        $deletionRequest = DeletionRequest::query()->sole();

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->post(route('deletion-requests.reject', $deletionRequest))
            ->assertRedirect(route('deletion-requests.index'));

        $this->assertRecordActive($record);
        $this->assertSame(DeletionRequestStatus::Rejected, $deletionRequest->fresh()->status);
    }

    public function test_rejected_or_approved_requests_do_not_flag_records_and_rejected_ones_can_be_requested_again(): void
    {
        [$gasJockey, $station, $owner] = $this->memberWithStation(Role::GasJockey);
        $rejected = Sale::factory()->create(['station_id' => $station->id, 'sold_at' => now()->subHour()]);
        $approvedElsewhere = Sale::factory()->create(['station_id' => $station->id, 'sold_at' => now()->subHours(2)]);
        $rejected->requestDeletion($gasJockey, 'First try', $station->business, $station)->reject($owner);
        DeletionRequest::factory()->approved()->for($approvedElsewhere, 'deletable')->create([
            'business_id' => $station->business_id,
        ]);

        $this->actingAs($gasJockey)->withUnencryptedCookie('station_id', $station->id);

        $this->get(route('sales.index'))
            ->assertInertia(fn ($page) => $page
                ->where('sales.data.0.id', $rejected->id)
                ->where('sales.data.0.deletion_pending', false)
                ->where('sales.data.1.id', $approvedElsewhere->id)
                ->where('sales.data.1.deletion_pending', false));

        $this->post(route('sales.deletion-request.store', $rejected), ['reason' => 'Second try'])
            ->assertRedirect();

        $this->assertSame(2, $rejected->deletionRequests()->count());
        $this->assertSame('Second try', $rejected->pendingDeletionRequest()->sole()->reason);

        $this->get(route('sales.index'))
            ->assertInertia(fn ($page) => $page->where('sales.data.0.deletion_pending', true));
    }

    public function test_admin_queue_keeps_names_of_deactivated_requesters_stations_and_customers(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $customer = Customer::factory()->create(['business_id' => $station->business_id, 'name' => 'Former Customer']);
        $payment = CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $station->id]);
        $payment->requestDeletion($cashier, 'Duplicate payment', $station->business, $station);

        $cashier->deactivate();
        $customer->deactivate();
        $station->deactivate();

        $this->actingAs($owner)
            ->get(route('deletion-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('requests.0.type', 'customer_payment')
                ->where('requests.0.label', 'Former Customer')
                ->where('requests.0.requested_by', $cashier->name)
                ->where('requests.0.station', $station->name));
    }

    public function test_cash_flow_entries_are_listed_with_their_date_only(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $entry = CashFlow::factory()->create(['station_id' => $station->id, 'occurred_at' => '2026-10-29']);
        $entry->requestDeletion($cashier, 'Wrong day', $station->business, $station);

        $this->actingAs($owner)
            ->get(route('deletion-requests.index'))
            ->assertInertia(fn ($page) => $page->where('requests.0.occurred_at', '2026-10-29'));
    }

    public function test_requests_survive_their_requester_being_deleted(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $deletionRequest = $sale->requestDeletion($cashier, 'Duplicate sale', $station->business, $station);

        $cashier->delete();

        $this->assertNull($deletionRequest->fresh()->requested_by);

        $this->actingAs($owner)
            ->get(route('deletion-requests.index'))
            ->assertInertia(fn ($page) => $page
                ->has('requests', 1)
                ->where('requests.0.requested_by', null)
                ->where('pending_deletion_requests', 1));
    }

    public function test_admin_deleting_a_record_directly_closes_its_pending_request(): void
    {
        [$gasJockey, $station, $owner] = $this->memberWithStation(Role::GasJockey);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $deletionRequest = $sale->requestDeletion($gasJockey, 'Wrong volume', $station->business, $station);

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->delete(route('sales.destroy', $sale))
            ->assertRedirect(route('sales.index'));

        $this->assertRecordDeactivated($sale);

        $deletionRequest->refresh();
        $this->assertSame(DeletionRequestStatus::Approved, $deletionRequest->status);
        $this->assertSame($owner->id, $deletionRequest->reviewed_by);
    }

    public function test_pending_requests_are_flagged_on_sales_and_counted_for_admins(): void
    {
        [$gasJockey, $station, $owner] = $this->memberWithStation(Role::GasJockey);
        $requested = Sale::factory()->create(['station_id' => $station->id, 'sold_at' => now()->subHour()]);
        Sale::factory()->create(['station_id' => $station->id, 'sold_at' => now()->subDays(2)]);
        $requested->requestDeletion($gasJockey, 'Test sale', $station->business, $station);

        $this->actingAs($gasJockey)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('sales.index'))
            ->assertInertia(fn ($page) => $page
                ->where('sales.data.0.id', $requested->id)
                ->where('sales.data.0.deletion_pending', true)
                ->where('sales.data.1.deletion_pending', false)
                ->where('pending_deletion_requests', 0));

        $this->actingAs($owner)
            ->withUnencryptedCookie('station_id', $station->id)
            ->get(route('sales.index'))
            ->assertInertia(fn ($page) => $page->where('pending_deletion_requests', 1));
    }

    public function test_pending_requests_are_flagged_on_cash_flow_customers_and_payments(): void
    {
        [$cashier, $station] = $this->memberWithStation(Role::Cashier);
        $entry = CashFlow::factory()->create(['station_id' => $station->id, 'occurred_at' => now()]);
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);
        $payment = CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $station->id]);

        foreach ([$entry, $customer, $payment] as $record) {
            $record->requestDeletion($cashier, 'Entered twice', $station->business, $station);
        }

        $this->actingAs($cashier)->withUnencryptedCookie('station_id', $station->id);

        $this->get(route('cash-flow.index'))
            ->assertInertia(fn ($page) => $page
                ->where('entries.0.id', $entry->id)
                ->where('entries.0.deletion_pending', true));

        $this->get(route('customers.index'))
            ->assertInertia(fn ($page) => $page->where('customers.0.deletion_pending', true));

        $this->get(route('customers.show', $customer))
            ->assertInertia(fn ($page) => $page->where('payments.data.0.deletion_pending', true));
    }

    public function test_reviewed_requests_do_not_flag_cash_flow_entries_customers_or_payments(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $entry = CashFlow::factory()->create(['station_id' => $station->id, 'occurred_at' => now()]);
        $customer = Customer::factory()->create(['business_id' => $station->business_id]);
        $payment = CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $station->id]);

        foreach ([$entry, $customer, $payment] as $record) {
            $record->requestDeletion($cashier, 'Entered twice', $station->business, $station)->reject($owner);
        }

        $this->actingAs($cashier)->withUnencryptedCookie('station_id', $station->id);

        $this->get(route('cash-flow.index'))
            ->assertInertia(fn ($page) => $page
                ->where('entries.0.id', $entry->id)
                ->where('entries.0.deletion_pending', false));

        $this->get(route('customers.index'))
            ->assertInertia(fn ($page) => $page->where('customers.0.deletion_pending', false));

        $this->get(route('customers.show', $customer))
            ->assertInertia(fn ($page) => $page->where('payments.data.0.deletion_pending', false));
    }

    public function test_admin_queue_keeps_pump_and_fuel_names_of_a_sale_after_they_are_deactivated(): void
    {
        [$gasJockey, $station, $owner] = $this->memberWithStation(Role::GasJockey);
        $sale = Sale::factory()->create(['station_id' => $station->id, 'volume' => 20]);
        $sale->requestDeletion($gasJockey, 'Wrong pump', $station->business, $station);
        $sale->pump->deactivate();
        $sale->fuelType->deactivate();

        $this->actingAs($owner)
            ->get(route('deletion-requests.index'))
            ->assertInertia(fn ($page) => $page->where(
                'requests.0.label',
                "{$sale->pump->name} · {$sale->fuelType->name} · 20.00 {$sale->fuelType->unit}",
            ));
    }

    public function test_requesting_a_deletion_locks_the_record_before_writing_the_request(): void
    {
        [$cashier, $station] = $this->memberWithStation(Role::Cashier);
        $sale = Sale::factory()->create(['station_id' => $station->id]);

        $order = $this->locksAndWritesDuring(
            fn () => $sale->requestDeletion($cashier, 'Duplicate sale', $station->business, $station),
        );

        $this->assertSame(['lock:sales', 'write:deletion_requests'], $order);
    }

    public function test_approvals_and_direct_deletes_take_the_record_before_the_request(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $approvedSale = Sale::factory()->create(['station_id' => $station->id]);
        $deletedSale = Sale::factory()->create(['station_id' => $station->id]);
        $deletionRequest = $approvedSale->requestDeletion($cashier, 'Duplicate sale', $station->business, $station);
        $deletedSale->requestDeletion($cashier, 'Duplicate sale', $station->business, $station);

        // Taking the record row first in both paths means they wait for each other instead of deadlocking.
        $this->assertSame(
            ['lock:sales', 'write:deletion_requests', 'write:sales', 'write:deletion_requests'],
            $this->locksAndWritesDuring(fn () => $deletionRequest->approve($owner)),
        );
        $this->assertSame(
            ['write:sales', 'write:deletion_requests'],
            $this->locksAndWritesDuring(fn () => $deletedSale->deleteApprovedBy($owner)),
        );
    }

    public function test_a_failed_approval_leaves_the_request_pending_and_the_record_in_place(): void
    {
        [$cashier, $station, $owner] = $this->memberWithStation(Role::Cashier);
        $sale = Sale::factory()->create(['station_id' => $station->id]);
        $deletionRequest = $sale->requestDeletion($cashier, 'Duplicate sale', $station->business, $station);

        Sale::updating(fn () => throw new RuntimeException('Deleting the sale failed.'));

        $this->assertThrows(fn () => $deletionRequest->approve($owner), RuntimeException::class);

        $this->assertTrue($deletionRequest->fresh()->isPending());
        $this->assertRecordActive($sale);
    }

    /**
     * A member with the given role at a business that has a station, plus the business owner.
     *
     * @return array{0: User, 1: Station, 2: User}
     */
    private function memberWithStation(Role $role): array
    {
        // Another business with two stations comes first, so business, station and record ids differ
        // and a check that compares the wrong ids cannot pass by accident.
        Station::factory()->count(2)->for(Business::factory())->create();

        $owner = User::factory()->create();
        $station = Station::factory()->for(Business::factory()->for($owner))->create();
        $member = User::factory()->state(['role' => $role])->create(['business_id' => $station->business_id]);

        return [$member, $station, $owner];
    }

    /**
     * The row locks taken and the tables written while $callback runs, in order.
     * SQLite has no row locks, so a lock is recorded when the query grammar compiles it.
     *
     * @return list<string>
     */
    private function locksAndWritesDuring(Closure $callback): array
    {
        $events = new ArrayObject;
        $connection = DB::connection();
        $originalGrammar = $connection->getQueryGrammar();

        $connection->setQueryGrammar(new class($connection, $events) extends SQLiteGrammar
        {
            public function __construct(Connection $connection, private ArrayObject $events)
            {
                parent::__construct($connection);
            }

            protected function compileLock(Builder $query, $value)
            {
                $this->events[] = 'lock:'.$query->from;

                return parent::compileLock($query, $value);
            }
        });

        DB::listen(function (QueryExecuted $query) use ($events): void {
            if (preg_match('/^(?:update|insert into) "(\w+)"/i', $query->sql, $matches)) {
                $events[] = 'write:'.$matches[1];
            }
        });

        try {
            $callback();
        } finally {
            $connection->setQueryGrammar($originalGrammar);
        }

        return $events->getArrayCopy();
    }

    /**
     * A record of the given type at the station, and the URL its delete request is sent to.
     *
     * @return array{0: Model, 1: string}
     */
    private function requestableRecord(string $type, Station $station): array
    {
        if ($type === 'sale') {
            $sale = Sale::factory()->create(['station_id' => $station->id]);

            return [$sale, route('sales.deletion-request.store', $sale)];
        }

        if ($type === 'cash_flow') {
            $entry = CashFlow::factory()->create(['station_id' => $station->id, 'occurred_at' => now()]);

            return [$entry, route('cash-flow.deletion-request.store', $entry)];
        }

        $customer = Customer::factory()->create(['business_id' => $station->business_id]);

        if ($type === 'customer') {
            return [$customer, route('customers.deletion-request.store', $customer)];
        }

        $payment = CustomerPayment::factory()->create(['customer_id' => $customer->id, 'station_id' => $station->id]);

        return [$payment, route('customers.payments.deletion-request.store', [$customer, $payment])];
    }

    private function assertRecordActive(Model $record): void
    {
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->getKey(), 'is_active' => true]);
    }

    private function assertRecordDeactivated(Model $record): void
    {
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->getKey(), 'is_active' => false]);
    }
}
