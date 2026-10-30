<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DeletionRequest;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeletionRequestController extends Controller
{
    public function index(): Response
    {
        // Related rows may have been deactivated since the request was made; keep showing their names.
        $requests = DeletionRequest::query()
            ->where('business_id', $this->currentBusiness()->id)
            ->pending()
            ->with([
                'deletable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    Sale::class => [
                        'pump' => fn ($query) => $query->withInactive()->select('id', 'name'),
                        'fuelType' => fn ($query) => $query->withInactive()->select('id', 'name', 'unit'),
                    ],
                    CustomerPayment::class => [
                        'customer' => fn ($query) => $query->withInactive()->select('id', 'name'),
                    ],
                ]),
                'requester' => fn ($query) => $query->withInactive()->select('id', 'name'),
                'station' => fn ($query) => $query->withInactive()->select('id', 'name'),
            ])
            ->latest()
            ->get()
            ->map(fn (DeletionRequest $deletionRequest): array => [
                'id' => $deletionRequest->id,
                'type' => $deletionRequest->deletable_type,
                ...$this->describe($deletionRequest->deletable),
                'station' => $deletionRequest->station?->name,
                'requested_by' => $deletionRequest->requester?->name,
                'reason' => $deletionRequest->reason,
                'requested_at' => $deletionRequest->created_at,
            ]);

        return Inertia::render('deletion-requests/index', [
            'requests' => $requests,
        ]);
    }

    public function approve(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorizeReview($deletionRequest);

        $this->flashReviewOutcome($deletionRequest->approve($request->user()), __('deletion_requests.approved'));

        return to_route('deletion-requests.index');
    }

    public function reject(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorizeReview($deletionRequest);

        $this->flashReviewOutcome($deletionRequest->reject($request->user()), __('deletion_requests.rejected'));

        return to_route('deletion-requests.index');
    }

    private function authorizeReview(DeletionRequest $deletionRequest): void
    {
        abort_unless($deletionRequest->business_id === $this->currentBusiness()->id, 403);
    }

    private function flashReviewOutcome(bool $reviewed, string $message): void
    {
        Inertia::flash('toast', $reviewed
            ? ['type' => 'success', 'message' => $message]
            : ['type' => 'info', 'message' => __('deletion_requests.already_reviewed')]);
    }

    /**
     * Summarize the record an admin is asked to delete.
     *
     * @return array{label: string|null, amount: float|null, occurred_at: mixed}
     */
    private function describe(?Model $record): array
    {
        return match (true) {
            $record instanceof Sale => [
                'label' => collect([
                    $record->pump?->name,
                    $record->fuelType?->name,
                    trim($record->volume.' '.$record->fuelType?->unit),
                ])->filter()->implode(' · '),
                'amount' => (float) $record->total_amount,
                'occurred_at' => $record->sold_at,
            ],
            $record instanceof CashFlow => [
                'label' => $record->description,
                'amount' => (float) $record->amount,
                'occurred_at' => $record->occurred_at?->toDateString(),
            ],
            $record instanceof Customer => [
                'label' => collect([$record->name, $record->phone])->filter()->implode(' · '),
                'amount' => null,
                'occurred_at' => null,
            ],
            $record instanceof CustomerPayment => [
                'label' => $record->customer?->name,
                'amount' => (float) $record->amount,
                'occurred_at' => $record->paid_at,
            ],
            default => ['label' => null, 'amount' => null, 'occurred_at' => null],
        };
    }
}
