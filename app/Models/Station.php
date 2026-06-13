<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasActiveScope, HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'address',
    ];

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return HasMany<FuelType, $this>
     */
    public function fuelTypes(): HasMany
    {
        return $this->hasMany(FuelType::class);
    }

    /**
     * @return HasMany<CustomerPayment, $this>
     */
    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * @return HasMany<Tank, $this>
     */
    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasMany<CashFlow, $this>
     */
    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }
}
