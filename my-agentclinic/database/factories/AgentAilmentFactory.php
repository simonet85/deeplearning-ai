<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentAilment;
use App\Models\Ailment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentAilment>
 */
class AgentAilmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'ailment_id' => Ailment::factory(),
            'severity' => fake()->numberBetween(1, 5),
            'notes' => fake()->sentence(),
        ];
    }
}
