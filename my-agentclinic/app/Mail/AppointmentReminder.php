<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentReminder extends Mailable
{
    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your session is tomorrow. Breathe in, breathe out.'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.appointment-reminder');
    }
}
