<?php

namespace Database\Factories;

use App\Models\Therapy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Therapy>
 */
class TherapyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'duration' => fake()->randomElement([30, 45, 60]),
            'type' => fake()->randomElement(['Cognitive', 'Computational', 'Social', 'Rest']),
        ];
    }
}
