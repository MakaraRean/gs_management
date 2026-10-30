<?php

namespace App\Traits;

use App\Enums\DeletionRequestStatus;
use App\Models\Business;
use App\Models\DeletionRequest;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

trait HasDeletionRequests
{
    /**
     * @return MorphMany<DeletionRequest, $this>
     */
    public function deletionRequests(): MorphMany
    {
        return $this->morphMany(DeletionRequest::class, 'deletable');
    }

    /**
     * @return MorphOne<DeletionRequest, $this>
     */
    public function pendingDeletionRequest(): MorphOne
    {
        return $this->morphOne(DeletionRequest::class, 'deletable')
            ->where('status', DeletionRequestStatus::Pending);
    }

    /**
     * Ask an admin to delete this record. Asking again while a request is pending changes nothing.
     *
     * The record is locked first, so concurrent requests cannot add duplicate pending requests
     * and a concurrent delete cannot leave a pending request for a record that is already gone.
     */
    public function requestDeletion(User $requester, string $reason, Business $business, ?Station $station): DeletionRequest
    {
        return DB::transaction(function () use ($requester, $reason, $business, $station): DeletionRequest {
            static::query()->whereKey($this->getKey())->lockForUpdate()->firstOr(fn () => abort(404));

            return $this->pendingDeletionRequest()->firstOrCreate([], [
                'business_id' => $business->id,
                'station_id' => $station?->id,
                'requested_by' => $requester->id,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * Delete this record on an admin's authority, closing any pending requests to delete it.
     */
    public function deleteApprovedBy(User $admin): void
    {
        DB::transaction(function () use ($admin): void {
            $this->deactivate();

            $this->deletionRequests()->pending()->update([
                'status' => DeletionRequestStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
        });
    }
}
