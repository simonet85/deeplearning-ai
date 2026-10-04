<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'availability_id' => Availability::factory(),
            'therapist_id' => fn (array $attributes) => Availability::find($attributes['availability_id'])->therapist_id,
            'therapy_id' => Therapy::factory(),
            'datetime' => fn (array $attributes) => Availability::find($attributes['availability_id'])->startsAt(),
            'status' => AppointmentStatus::Booked,
        ];
    }

    /** A cancelled appointment has released its slot, so no availability row is created for it. */
    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::Cancelled,
            'availability_id' => null,
            'therapist_id' => User::factory(),
            'datetime' => now()->addDay(),
        ]);
    }
}
