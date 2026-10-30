<?php

namespace App\Models;

use App\Enums\DeletionRequestStatus;
use Database\Factories\DeletionRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class DeletionRequest extends Model
{
    /** @use HasFactory<DeletionRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'business_id',
        'station_id',
        'requested_by',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeletionRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * The sale, cash flow entry, customer or customer payment to delete.
     *
     * @return MorphTo<Model, $this>
     */
    public function deletable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DeletionRequestStatus::Pending);
    }

    public function isPending(): bool
    {
        return $this->status === DeletionRequestStatus::Pending;
    }

    /**
     * Close this request and delete the requested record, if it still exists.
     * Returns false when someone else already reviewed the request.
     */
    public function approve(User $reviewer): bool
    {
        return DB::transaction(function () use ($reviewer): bool {
            // Lock the record before the request, in the same order as direct deletes and new
            // requests take their locks, so concurrent reviews and deletes cannot deadlock.
            $record = $this->deletable()->lockForUpdate()->first();

            if (! $this->close(DeletionRequestStatus::Approved, $reviewer)) {
                return false;
            }

            $record?->deleteApprovedBy($reviewer);

            return true;
        });
    }

    /**
     * Close this request and keep the requested record.
     * Returns false when someone else already reviewed the request.
     */
    public function reject(User $reviewer): bool
    {
        return $this->close(DeletionRequestStatus::Rejected, $reviewer);
    }

    /**
     * Close the request only while it is still pending, so concurrent reviews cannot both win.
     */
    private function close(DeletionRequestStatus $status, User $reviewer): bool
    {
        $closed = static::query()
            ->whereKey($this->getKey())
            ->pending()
            ->update([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

        return $closed === 1;
    }
}
