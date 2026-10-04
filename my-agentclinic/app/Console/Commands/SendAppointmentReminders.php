<?php

namespace App\Console\Commands;

use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reminders:send')]
#[Description('Queue a reminder e-mail for every booked appointment starting within 24 hours')]
class SendAppointmentReminders extends Command
{
    public function handle(): int
    {
        $appointments = Appointment::needsReminder()->get();

        foreach ($appointments as $appointment) {
            SendAppointmentReminder::dispatch($appointment);
        }

        $this->info("Queued {$appointments->count()} reminder(s).");

        return self::SUCCESS;
    }
}
