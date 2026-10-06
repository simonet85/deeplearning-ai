<?php

namespace App\Jobs;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function handle(): void
    {
        $appointment = $this->appointment->fresh();

        // The appointment may have been cancelled, reminded by an earlier job, or lost its address since queuing.
        if ($appointment->status !== AppointmentStatus::Booked || $appointment->reminder_sent_at !== null) {
            return;
        }

        $email = $appointment->agent->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->locale($appointment->agent->mailLocale())->send(new AppointmentReminder($appointment));

        $appointment->update(['reminder_sent_at' => now()]);
    }
}
