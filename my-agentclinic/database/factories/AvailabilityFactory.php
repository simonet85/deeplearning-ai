<?php

namespace Database\Factories;

use App\Models\Availability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'therapist_id' => User::factory(),
            'date' => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'time_slot' => fake()->randomElement(['09:00', '10:00', '11:00', '14:00', '15:00']),
        ];
    }
}
