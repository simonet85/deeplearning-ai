<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Therapy;
use App\Models\TherapyRating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TherapyRating>
 */
class TherapyRatingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'therapy_id' => Therapy::factory(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
