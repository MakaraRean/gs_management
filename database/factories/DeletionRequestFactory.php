<?php

namespace Database\Factories;

use App\Enums\DeletionRequestStatus;
use App\Models\Business;
use App\Models\DeletionRequest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeletionRequest>
 */
class DeletionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'station_id' => null,
            'deletable_type' => 'sale',
            'deletable_id' => Sale::factory(),
            'requested_by' => User::factory(),
            'reason' => fake()->sentence(),
            'status' => DeletionRequestStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    /**
     * Indicate that an admin approved the request.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeletionRequestStatus::Approved,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that an admin rejected the request.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeletionRequestStatus::Rejected,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }
}
