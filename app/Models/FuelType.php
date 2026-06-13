<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Database\Factories\FuelTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelType extends Model
{
    /** @use HasFactory<FuelTypeFactory> */
    use HasActiveScope, HasFactory;

    protected $fillable = [
        'station_id',
        'name',
        'unit_price',
        'unit',
        'color',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
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
}
