<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasActiveScope, HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'customers_per_station',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customers_per_station' => 'boolean',
        ];
    }

    /**
     * The owner/creator of the business.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Users that belong to this business (members/staff).
     *
     * @return HasMany<User, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Station, $this>
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
