<?php

namespace Tests\Feature;

use App\Actions\BookAppointment;
use App\Jobs\SendAppointmentReminder;
use App\Mail\AppointmentBooked;
use App\Mail\AppointmentReminder;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The mails are really rendered here (the "array" mailer keeps what would have been sent), so the language of the
 * subject and of the body is checked as a recipient would read them.
 */
class EmailLanguageTest extends TestCase
{
    use RefreshDatabase;

    /** A booking for a known Monday, so the date can be checked in both languages. */
    private function book(Agent $agent): void
    {
        $slot = Availability::factory()->create(['date' => '2030-03-04', 'time_slot' => '09:15']);

        app(BookAppointment::class)->handle($agent, Therapy::factory()->create(['duration' => 45]), $slot->id);
    }

    /** @return list<\Symfony\Component\Mailer\SentMessage> */
    private function sent(): array
    {
        return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all();
    }

    private function subjectOf($message): string
    {
        return $message->getOriginalMessage()->getSubject();
    }

    private function bodyOf($message): string
    {
        return $message->getOriginalMessage()->getHtmlBody();
    }

    private function agentWithLocale(?string $locale, string $email = 'wexley@agents.test'): Agent
    {
        $user = User::factory()->agent()->create(['email' => $email, 'locale' => $locale]);

        return $user->agent;
    }

    // ---- the language of an agent's mail ----

    public function test_the_mail_language_is_the_accounts_language_or_the_default(): void
    {
        $this->assertSame('fr', $this->agentWithLocale('fr')->mailLocale());
        $this->assertSame('en', $this->agentWithLocale('en', 'a@agents.test')->mailLocale());
        $this->assertSame('en', $this->agentWithLocale(null, 'b@agents.test')->mailLocale());
        $this->assertSame('en', Agent::factory()->create()->mailLocale(), 'An agent without an account gets the default');

        $unsupported = $this->agentWithLocale(null, 'c@agents.test');
        $unsupported->user->forceFill(['locale' => 'de'])->save();
        $this->assertSame('en', $unsupported->fresh()->mailLocale());
    }

    // ---- the booking confirmation ----

    public function test_a_confirmation_is_sent_in_french_to_an_agent_who_chose_french(): void
    {
        $this->book($this->agentWithLocale('fr'));

        [$message] = $this->sent();

        $this->assertSame('Votre séance est réservée. Respirez profondément.', $this->subjectOf($message));
        $body = $this->bodyOf($message);
        $this->assertStringContainsString('Bonjour,', $body);
        $this->assertStringContainsString('Votre rendez-vous est réservé.', $body);
        $this->assertStringContainsString('Thérapie', $body);
        $this->assertStringContainsString('Thérapeute', $body);
        $this->assertStringContainsString('45 minutes', $body);
        $this->assertStringContainsString('lundi 4 mars 2030 à 09:15', $body);
        $this->assertStringNotContainsString('Your appointment is booked', $body);
    }

    public function test_a_confirmation_is_sent_in_english_to_an_agent_who_chose_english(): void
    {
        $this->book($this->agentWithLocale('en'));

        [$message] = $this->sent();

        $this->assertSame('Your session is booked. Deep breaths.', $this->subjectOf($message));
        $this->assertStringContainsString('Your appointment is booked.', $this->bodyOf($message));
        $this->assertStringContainsString('Monday, March 4, 2030 at 09:15', $this->bodyOf($message));
    }

    public function test_the_language_of_the_person_booking_does_not_matter(): void
    {
        // A French-speaking staff member books for an agent whose account is in English, and the other way round.
        app()->setLocale('fr');
        $this->book($this->agentWithLocale('en'));

        app()->setLocale('en');
        $this->book($this->agentWithLocale('fr', 'second@agents.test'));

        [$first, $second] = $this->sent();

        $this->assertSame('Your session is booked. Deep breaths.', $this->subjectOf($first));
        $this->assertSame('Votre séance est réservée. Respirez profondément.', $this->subjectOf($second));
    }

    public function test_an_agent_without_an_account_gets_english_even_when_staff_use_french(): void
    {
        app()->setLocale('fr');

        $this->book(Agent::factory()->create(['email' => 'nobody@agents.test']));

        [$message] = $this->sent();

        $this->assertSame('Your session is booked. Deep breaths.', $this->subjectOf($message));
    }

    public function test_sending_does_not_change_the_language_of_the_running_request(): void
    {
        app()->setLocale('en');

        $this->book($this->agentWithLocale('fr'));

        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_mailable_carries_the_agents_language(): void
    {
        Mail::fake();

        $this->book($this->agentWithLocale('fr'));

        Mail::assertSent(AppointmentBooked::class, fn (AppointmentBooked $mail) => $mail->locale === 'fr' && $mail->hasTo('wexley@agents.test'));
    }

    // ---- the reminder ----

    private function dueReminder(Agent $agent): Appointment
    {
        return Appointment::factory()->create([
            'agent_id' => $agent->id,
            'datetime' => Carbon::parse('2030-03-04 09:15'),
            'created_at' => now()->subYears(2),
        ]);
    }

    public function test_a_reminder_is_sent_in_the_agents_language(): void
    {
        $french = $this->dueReminder($this->agentWithLocale('fr'));
        $english = $this->dueReminder($this->agentWithLocale('en', 'second@agents.test'));
        $none = $this->dueReminder(Agent::factory()->create(['email' => 'third@agents.test']));

        foreach ([$french, $english, $none] as $appointment) {
            SendAppointmentReminder::dispatchSync($appointment);
        }

        [$first, $second, $third] = $this->sent();

        $this->assertSame('Votre séance a lieu demain. Inspirez, expirez.', $this->subjectOf($first));
        $this->assertStringContainsString('Re-bonjour,', $this->bodyOf($first));
        $this->assertStringContainsString('lundi 4 mars 2030 à 09:15', $this->bodyOf($first));

        $this->assertSame('Your session is tomorrow. Breathe in, breathe out.', $this->subjectOf($second));
        $this->assertStringContainsString('Hello again,', $this->bodyOf($second));

        $this->assertSame('Your session is tomorrow. Breathe in, breathe out.', $this->subjectOf($third));
    }

    public function test_a_queued_reminder_keeps_the_agents_language_whatever_the_worker_uses(): void
    {
        $appointment = $this->dueReminder($this->agentWithLocale('fr'));

        // What a worker does: the job travels through the queue as text and runs in a process with its own language.
        $job = unserialize(serialize(new SendAppointmentReminder($appointment)));
        app()->setLocale('en');
        $job->handle();

        $this->assertSame('Votre séance a lieu demain. Inspirez, expirez.', $this->subjectOf($this->sent()[0]));
        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_reminder_mailable_is_marked_with_the_agents_language(): void
    {
        Mail::fake();

        SendAppointmentReminder::dispatchSync($this->dueReminder($this->agentWithLocale('fr')));

        Mail::assertSent(AppointmentReminder::class, fn (AppointmentReminder $mail) => $mail->locale === 'fr');
    }

    // ---- the translations of the mails themselves ----

    public function test_every_line_of_both_mails_is_translated(): void
    {
        $appointment = $this->dueReminder($this->agentWithLocale('fr'));

        foreach ([new AppointmentBooked($appointment), new AppointmentReminder($appointment)] as $mail) {
            $english = $mail->locale('en')->render();
            $french = $mail->locale('fr')->render();

            $this->assertNotSame($english, $french);
            $this->assertStringNotContainsString('humans', $french);
        }
    }
}
