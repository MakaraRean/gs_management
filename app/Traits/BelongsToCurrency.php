<?php

namespace App\Traits;

use App\Models\Currency;
use App\Models\Station;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For records that hold an amount and remember the currency it was recorded in.
 *
 * A new record without a currency is stamped with its station's effective currency, so no
 * create path has to remember to set one.
 */
trait BelongsToCurrency
{
    public static function bootBelongsToCurrency(): void
    {
        static::creating(function (Model $model): void {
            $model->currency_id ??= $model->currencyStation()?->effective_currency->id;
        });
    }

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

    public function scopeForStation(Builder $query, Station|int $station): Builder
    {
        return $query->where(
            $query->getModel()->getTable().'.station_id',
            $station instanceof Station ? $station->id : $station,
        );
    }

    /**
     * The records the active station reports on: its own, in its current currency.
     */
    public function scopeInStationCurrency(Builder $query, Station $station, Currency $currency): Builder
    {
        return $query->forStation($station)->inCurrency($currency);
    }

    protected function currencyStationId(): ?int
    {
        return $this->station_id;
    }

    protected function currencyStation(): ?Station
    {
        $stationId = $this->currencyStationId();

        return $stationId
            ? Station::query()->withInactive()->with(['currency', 'business.currency'])->find($stationId)
            : null;
    }
}
