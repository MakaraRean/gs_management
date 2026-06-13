<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Database\Factories\TankFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tank extends Model
{
    /** @use HasFactory<TankFactory> */
    use HasActiveScope, HasFactory;

    protected $fillable = [
        'name',
        'fuel_type_id',
        'station_id',
        'capacity',
        'current_volume',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:2',
            'current_volume' => 'decimal:2',
        ];
    }

    /**
     * Add fuel to the tank, capped at capacity.
     */
    public function increaseVolume(float $volume): void
    {
        $this->current_volume = min((float) $this->capacity, (float) $this->current_volume + $volume);
        $this->save();
    }

    /**
     * Remove fuel from the tank, floored at zero.
     */
    public function decreaseVolume(float $volume): void
    {
        $this->current_volume = max(0, (float) $this->current_volume - $volume);
        $this->save();
    }

    /**
     * Percentage of capacity currently filled (0-100).
     */
    public function fillPercentage(): float
    {
        if ((float) $this->capacity <= 0) {
            return 0;
        }

        return round((float) $this->current_volume / (float) $this->capacity * 100, 1);
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * @return BelongsTo<FuelType, $this>
     */
    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    /**
     * @return HasMany<Pump, $this>
     */
    public function pumps(): HasMany
    {
        return $this->hasMany(Pump::class);
    }

    /**
     * @return HasMany<FuelDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(FuelDelivery::class);
    }
}
