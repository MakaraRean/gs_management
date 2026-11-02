<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use App\Traits\HasDeletionRequests;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasActiveScope, HasDeletionRequests, HasFactory;

    protected $fillable = [
        'business_id',
        'station_id',
        'address_id',
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
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
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

    /**
     * What the customer still owes in each currency, for the currencies they owe anything in.
     *
     * @return array<int, float> amount keyed by currency id
     */
    public function outstandingBalances(): array
    {
        $credited = $this->sales()
            ->where('payment_method', 'credit')
            ->selectRaw('currency_id, SUM(total_amount) as total')
            ->groupBy('currency_id')
            ->pluck('total', 'currency_id');

        $paid = $this->payments()
            ->selectRaw('currency_id, SUM(amount) as total')
            ->groupBy('currency_id')
            ->pluck('total', 'currency_id');

        return $credited
            ->map(fn ($total, $currencyId): float => (float) $total - (float) ($paid[$currencyId] ?? 0))
            ->filter(fn (float $balance): bool => $balance > 0)
            ->all();
    }

    /**
     * What the customer still owes; with a currency, only credit and payments recorded in it count.
     */
    public function outstandingBalance(?Currency $currency = null): float
    {
        $credited = (float) $this->sales()
            ->where('payment_method', 'credit')
            ->when($currency, fn ($query) => $query->inCurrency($currency))
            ->sum('total_amount');

        $paid = (float) $this->payments()
            ->when($currency, fn ($query) => $query->inCurrency($currency))
            ->sum('amount');

        return max(0, $credited - $paid);
    }
}
