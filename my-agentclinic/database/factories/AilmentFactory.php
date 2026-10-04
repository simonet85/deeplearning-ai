<?php

namespace Database\Factories;

use App\Models\Ailment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ailment>
 */
class AilmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'severity_scale' => 5,
        ];
    }
}
