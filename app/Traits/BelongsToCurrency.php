<?php

namespace App\Traits;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For records that hold an amount and remember the currency it was recorded in.
 */
trait BelongsToCurrency
{
    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeInCurrency(Builder $query, Currency|int $currency): Builder
    {
        return $query->where(
            $query->getModel()->getTable().'.currency_id',
            $currency instanceof Currency ? $currency->id : $currency,
        );
    }
}
