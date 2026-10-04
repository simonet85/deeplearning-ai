<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $slot = Availability::factory()->create();

        return [
            'agent_id' => Agent::factory(),
            'therapist_id' => $slot->therapist_id,
            'therapy_id' => Therapy::factory(),
            'availability_id' => $slot->id,
            'datetime' => $slot->startsAt(),
            'status' => AppointmentStatus::Booked,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => AppointmentStatus::Cancelled, 'availability_id' => null]);
    }
}
