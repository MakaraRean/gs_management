<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Database\Factories\FuelDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelDelivery extends Model
{
    /** @use HasFactory<FuelDeliveryFactory> */
    use HasActiveScope, HasFactory;

    protected $fillable = [
        'tank_id',
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
     * @return BelongsTo<Tank, $this>
     */
    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}
