<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\Station;
use App\Models\User;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_currency_list_ships_with_the_migration(): void
    {
        $this->assertSame(['KHR', 'THB', 'USD', 'VND'], Currency::query()->active()->orderBy('code')->pluck('code')->all());

        $usd = CurrencyFactory::seeded('USD');
        $khr = CurrencyFactory::seeded('KHR');

        $this->assertSame(['$', 2], [$usd->symbol, $usd->round_digits]);
        $this->assertSame(['៛', 0], [$khr->symbol, $khr->round_digits]);
    }

    public function test_a_currency_rounds_amounts_to_its_own_digits(): void
    {
        $usd = CurrencyFactory::seeded('USD');
        $khr = CurrencyFactory::seeded('KHR');

        $this->assertSame(1234.57, $usd->round(1234.567));
        $this->assertSame(1235.0, $khr->round(1234.567));
        $this->assertSame(4500.0, $khr->round('4500.37'));
        $this->assertSame(0.0, $khr->round(null));
    }

    public function test_the_in_currency_scope_matches_a_currency_or_its_id(): void
    {
        $usdStation = Station::factory()->create();
        $khr = CurrencyFactory::seeded('KHR');
        $usdSale = Sale::factory()->create(['station_id' => $usdStation->id]);
        $khrSale = Sale::factory()->create(['currency_id' => $khr->id]);

        $this->assertSame([$khrSale->id], Sale::query()->inCurrency($khr)->pluck('id')->all());
        $this->assertSame([$usdSale->id], Sale::query()->inCurrency($usdStation->effective_currency->id)->pluck('id')->all());
    }

    public function test_a_station_uses_its_own_currency_else_its_business_currency(): void
    {
        $business = Business::factory()->create();
        $inheriting = Station::factory()->for($business)->create();
        $overriding = Station::factory()->for($business)->create(['currency_id' => CurrencyFactory::seeded('KHR')->id]);

        $this->assertSame('USD', $inheriting->effective_currency->code);
        $this->assertSame('KHR', $overriding->effective_currency->code);

        $business->update(['currency_id' => CurrencyFactory::seeded('THB')->id]);

        $this->assertSame('THB', $inheriting->fresh()->effective_currency->code);
        $this->assertSame('KHR', $overriding->fresh()->effective_currency->code);
    }

    public function test_a_currency_in_use_cannot_be_deleted(): void
    {
        $business = Business::factory()->create();

        $this->expectException(QueryException::class);

        $business->currency->delete();
    }

    public function test_pages_share_every_currency_and_the_active_stations_effective_currency(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create();
        $inheriting = Station::factory()->for($business)->create();
        $khr = CurrencyFactory::seeded('KHR');
        $overriding = Station::factory()->for($business)->create(['currency_id' => $khr->id]);

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $inheriting->id)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('currencies', 4)
                ->where('current_station.currency.code', 'USD')
                ->where('current_station.currency.symbol', '$')
                ->where('current_station.currency.round_digits', 2));

        $this->actingAs($user)
            ->withUnencryptedCookie('station_id', $overriding->id)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('current_station.currency.id', $khr->id)
                ->where('current_station.currency.symbol', '៛')
                ->where('current_station.currency.round_digits', 0));
    }

    public function test_guests_are_not_sent_the_currency_list(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page->where('currencies', []));
    }
}
