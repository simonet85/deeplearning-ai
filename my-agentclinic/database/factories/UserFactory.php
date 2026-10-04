<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use App\Support\Access;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /** Every user is a therapist unless a state below replaces the role. */
    public function configure(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Access::role('therapist')));
    }

    public function admin(): static
    {
        return $this->withRole('admin');
    }

    /** An agent account with no agent record (a bare account). */
    public function agentAccount(): static
    {
        return $this->withRole('agent');
    }

    /** An agent account together with its linked agent record. */
    public function agent(): static
    {
        return $this->agentAccount()->afterCreating(fn (User $user) => Agent::factory()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]));
    }

    /** The user holds this role instead of the default one. */
    public function withRole(string $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles(Access::role($role)));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
