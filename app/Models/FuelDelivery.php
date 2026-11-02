<?php

namespace App\Models;

use App\Traits\BelongsToCurrency;
use App\Traits\HasActiveScope;
use Database\Factories\FuelDeliveryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelDelivery extends Model
{
    /** @use HasFactory<FuelDeliveryFactory> */
    use BelongsToCurrency, HasActiveScope, HasFactory;

    protected $fillable = [
        'tank_id',
        'currency_id',
        'volume',
        'cost_per_unit',
        'total_cost',
        'supplier',
        'delivered_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * A delivery belongs to a station through its tank, which may have been deactivated since.
     */
    public function scopeForStation(Builder $query, Station|int $station): Builder
    {
        return $query->whereHas(
            'tank',
            fn (Builder $tank) => $tank->withInactive()->where('station_id', $station instanceof Station ? $station->id : $station),
        );
    }

    protected function currencyStationId(): ?int
    {
        return Tank::query()->withInactive()->whereKey($this->tank_id)->value('station_id');
    }

    /**
     * @return BelongsTo<Tank, $this>
     */
    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}
