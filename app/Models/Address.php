<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Address extends Model
{
    use HasActiveScope, HasFactory;

    protected $fillable = [
        'street',
        'city',
        'province',
        'country',
    ];

    /**
     * @return BelongsToMany<Customer, $this>
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_address')
            ->withPivot(['label', 'is_default', 'is_active'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }
}
