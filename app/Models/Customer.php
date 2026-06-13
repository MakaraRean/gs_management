<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasActiveScope, HasFactory;

    protected $fillable = [
        'business_id',
        'station_id',
        'name',
        'phone',
        'email',
        'notes',
        'credit_limit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
        ];
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
     * @return BelongsToMany<Address, $this>
     */
    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'customer_address')
            ->withPivot(['label', 'is_default', 'is_active'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasMany<CustomerPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function outstandingBalance(): float
    {
        $credited = (float) $this->sales()
            ->where('payment_method', 'credit')
            ->sum('total_amount');

        $paid = (float) $this->payments()->sum('amount');

        return max(0, $credited - $paid);
    }
}
