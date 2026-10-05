<?php

namespace App\Actions;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentBooked;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Books an open availability slot for an agent. Staff and agents book through this same action, so the
 * guarantee that a slot is booked at most once, and the confirmation e-mail, are identical for both.
 */
class BookAppointment
{
    /**
     * @throws ValidationException keyed `availabilityId` when the slot is already booked or has started
     */
    public function handle(Agent $agent, Therapy $therapy, int $availabilityId): Appointment
    {
        $appointment = DB::transaction(function () use ($agent, $therapy, $availabilityId) {
            $slot = Availability::lockForUpdate()->findOrFail($availabilityId);

            if ($slot->appointment()->exists() || $slot->startsAt()->isPast()) {
                throw ValidationException::withMessages([
                    'availabilityId' => __('That slot is no longer available.'),
                ]);
            }

            return Appointment::create([
                'agent_id' => $agent->id,
                'therapist_id' => $slot->therapist_id,
                'therapy_id' => $therapy->id,
                'availability_id' => $slot->id,
                'datetime' => $slot->startsAt(),
                'status' => AppointmentStatus::Booked,
            ]);
        });

        if ($agent->email) {
            Mail::to($agent->email)->locale($agent->mailLocale())->send(new AppointmentBooked($appointment));
        }

        return $appointment;
    }
}
