<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentReminder;
use App\Mail\AppointmentReminder;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    /** An appointment starting in 10 hours that was booked two days ago, for an agent with an address. */
    private function due(array $overrides = []): Appointment
    {
        $agent = Agent::factory()->create(['email' => fake()->unique()->safeEmail()]);

        return Appointment::factory()->create([
            'agent_id' => $agent->id,
            'datetime' => now()->addHours(10),
            'created_at' => now()->subDays(2),
            ...$overrides,
        ]);
    }

    // ---- scope ----

    public function test_a_booked_appointment_starting_within_a_day_needs_a_reminder(): void
    {
        $due = $this->due();

        $this->assertSame([$due->id], Appointment::needsReminder()->pluck('id')->all());
    }

    public function test_the_scope_excludes_everything_that_should_not_be_reminded(): void
    {
        $this->due(['status' => AppointmentStatus::Cancelled]);
        $this->due(['status' => AppointmentStatus::Completed]);
        $this->due(['reminder_sent_at' => now()->subHour()]);
        $this->due(['datetime' => now()->addHours(30)]);
        $this->due(['datetime' => now()->subHour()]);
        $this->due(['created_at' => now()->subHours(2)]); // booked inside the window
        Appointment::factory()->create([
            'agent_id' => Agent::factory()->create(['email' => null])->id,
            'datetime' => now()->addHours(10),
            'created_at' => now()->subDays(2),
        ]);

        $this->assertSame(0, Appointment::needsReminder()->count());
    }

    // ---- mailable ----

    public function test_the_reminder_mailable_has_the_expected_content(): void
    {
        $appointment = $this->due();
        $appointment->agent->update(['name' => 'Pixel']);
        $appointment->therapy->update(['name' => 'Context Window Spa', 'duration' => 45]);

        $mailable = new AppointmentReminder($appointment->fresh());

        $mailable->assertHasSubject('Your session is tomorrow. Breathe in, breathe out.');
        $mailable->assertSeeInHtml('Hello again, Pixel.');
        $mailable->assertSeeInHtml('Context Window Spa');
        $mailable->assertSeeInHtml($appointment->therapist->name);
        $mailable->assertSeeInHtml('45 minutes');
        $mailable->assertSeeInHtml($appointment->datetime->format('H:i'));
    }

    // ---- job ----

    public function test_the_job_sends_the_reminder_and_marks_the_appointment(): void
    {
        Mail::fake();
        $appointment = $this->due();

        SendAppointmentReminder::dispatchSync($appointment);

        Mail::assertSent(AppointmentReminder::class, fn ($mail) => $mail->hasTo($appointment->agent->email)
            && $mail->appointment->is($appointment));
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    public function test_the_job_does_nothing_if_the_appointment_was_cancelled_meanwhile(): void
    {
        Mail::fake();
        $appointment = $this->due();
        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        SendAppointmentReminder::dispatchSync($appointment);

        Mail::assertNothingSent();
        $this->assertNull($appointment->fresh()->reminder_sent_at);
    }

    public function test_the_job_does_nothing_if_a_reminder_was_already_sent(): void
    {
        Mail::fake();
        $appointment = $this->due(['reminder_sent_at' => now()->subMinute()]);

        SendAppointmentReminder::dispatchSync($appointment);

        Mail::assertNothingSent();
    }

    public function test_the_job_does_nothing_if_the_agent_lost_their_address(): void
    {
        Mail::fake();
        $appointment = $this->due();
        $appointment->agent->update(['email' => null]);

        SendAppointmentReminder::dispatchSync($appointment);

        Mail::assertNothingSent();
        $this->assertNull($appointment->fresh()->reminder_sent_at);
    }

    // ---- command ----

    public function test_the_command_queues_one_job_per_due_appointment(): void
    {
        Queue::fake();
        $one = $this->due();
        $two = $this->due();
        $this->due(['status' => AppointmentStatus::Cancelled]);

        $this->artisan('reminders:send')
            ->expectsOutput('Queued 2 reminder(s).')
            ->assertSuccessful();

        Queue::assertPushed(SendAppointmentReminder::class, 2);
        Queue::assertPushed(SendAppointmentReminder::class, fn ($job) => $job->appointment->is($one));
        Queue::assertPushed(SendAppointmentReminder::class, fn ($job) => $job->appointment->is($two));
    }

    public function test_the_command_queues_nothing_when_nothing_is_due(): void
    {
        Queue::fake();

        $this->artisan('reminders:send')->expectsOutput('Queued 0 reminder(s).')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_running_the_command_twice_sends_each_reminder_once(): void
    {
        Mail::fake();
        $appointment = $this->due();

        $this->artisan('reminders:send')->assertSuccessful();
        $this->artisan('reminders:send')->expectsOutput('Queued 0 reminder(s).')->assertSuccessful();

        Mail::assertSent(AppointmentReminder::class, 1);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    public function test_the_command_runs_hourly_on_the_schedule(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'reminders:send'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }

    // ---- card marker ----

    public function test_the_appointment_card_shows_when_a_reminder_was_sent(): void
    {
        $appointment = $this->due(['reminder_sent_at' => now()->setTime(8, 30)]);

        Volt::actingAs(User::factory()->create())->test('appointments')
            ->assertSee('Reminder sent '.$appointment->fresh()->reminder_sent_at->format('M j, H:i'));
    }

    public function test_the_appointment_card_has_no_marker_before_a_reminder(): void
    {
        $this->due();

        Volt::actingAs(User::factory()->create())->test('appointments')
            ->assertDontSee('Reminder sent');
    }
}
