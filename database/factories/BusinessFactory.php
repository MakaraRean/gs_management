<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'customers_per_station' => false,
        ];
    }

    /**
     * Make the owner a member of the business they created (membership scoping).
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Business $business): void {
            $owner = $business->user;

            if ($owner && $owner->business_id === null) {
                $owner->forceFill(['business_id' => $business->id])->save();
            }
        });
    }
}
