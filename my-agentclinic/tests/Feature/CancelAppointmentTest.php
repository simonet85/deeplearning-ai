<?php

namespace Tests\Feature;

use App\Actions\CancelAppointment;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_cancels_a_booked_appointment_and_frees_its_slot(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertTrue(app(CancelAppointment::class)->handle($appointment));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertNull($appointment->availability_id);
    }

    public function test_it_leaves_other_statuses_untouched(): void
    {
        foreach ([AppointmentStatus::Completed, AppointmentStatus::Cancelled] as $status) {
            $appointment = Appointment::factory()->create(['status' => $status]);
            $slot = $appointment->availability_id;

            $this->assertFalse(app(CancelAppointment::class)->handle($appointment));

            $this->assertSame($status, $appointment->fresh()->status);
            $this->assertSame($slot, $appointment->fresh()->availability_id);
        }
    }
}
