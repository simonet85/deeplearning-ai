<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;

/**
 * Cancels a booked appointment and frees its slot so it can be booked again. Staff and agents cancel through
 * this same action; anything that is not currently booked is left untouched.
 */
class CancelAppointment
{
    /** @return bool whether the appointment was cancelled by this call */
    public function handle(Appointment $appointment): bool
    {
        if ($appointment->status !== AppointmentStatus::Booked) {
            return false;
        }

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'availability_id' => null,
        ]);

        return true;
    }
}
