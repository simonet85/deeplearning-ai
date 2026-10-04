<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->firstName().'-'.fake()->numberBetween(1, 9),
            'agent_type' => fake()->randomElement(['Coding assistant', 'Research agent', 'Support bot', 'Planner']),
            'bio' => fake()->sentence(),
        ];
    }

    /** An agent record owned by a (bare) agent account. */
    public function forUser(): static
    {
        return $this->state(fn () => ['user_id' => User::factory()->agentAccount()]);
    }
}
