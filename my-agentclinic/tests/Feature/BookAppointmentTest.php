<?php

namespace Tests\Feature;

use App\Actions\BookAppointment;
use App\Enums\AppointmentStatus;
use App\Mail\AppointmentBooked;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private function book(Agent $agent, Therapy $therapy, Availability $slot): Appointment
    {
        return app(BookAppointment::class)->handle($agent, $therapy, $slot->id);
    }

    public function test_it_books_the_slot_for_the_agent(): void
    {
        Mail::fake();
        $agent = Agent::factory()->create();
        $therapy = Therapy::factory()->create();
        $slot = Availability::factory()->create(['time_slot' => '10:00']);

        $appointment = $this->book($agent, $therapy, $slot);

        $this->assertTrue($appointment->exists);
        $this->assertSame($agent->id, $appointment->agent_id);
        $this->assertSame($therapy->id, $appointment->therapy_id);
        $this->assertSame($slot->therapist_id, $appointment->therapist_id);
        $this->assertSame($slot->id, $appointment->availability_id);
        $this->assertSame(AppointmentStatus::Booked, $appointment->status);
        $this->assertTrue($appointment->datetime->equalTo($slot->startsAt()));
    }

    public function test_it_sends_the_confirmation_when_the_agent_has_an_address(): void
    {
        Mail::fake();
        $agent = Agent::factory()->create(['email' => 'pixel@agents.test']);

        $appointment = $this->book($agent, Therapy::factory()->create(), Availability::factory()->create());

        Mail::assertSent(AppointmentBooked::class, fn ($mail) => $mail->hasTo('pixel@agents.test')
            && $mail->appointment->is($appointment));
        Mail::assertSentCount(1);
    }

    public function test_it_sends_nothing_without_an_address(): void
    {
        Mail::fake();

        $this->book(Agent::factory()->create(['email' => null]), Therapy::factory()->create(), Availability::factory()->create());

        $this->assertDatabaseCount('appointments', 1);
        Mail::assertNothingSent();
    }

    public function test_a_taken_slot_is_rejected_with_nothing_created_or_sent(): void
    {
        Mail::fake();
        $taken = Appointment::factory()->create();

        try {
            $this->book(Agent::factory()->create(['email' => 'late@agents.test']), Therapy::factory()->create(), $taken->availability);
            $this->fail('Expected the taken slot to be rejected.');
        } catch (ValidationException $e) {
            $this->assertSame(['availabilityId'], array_keys($e->errors()));
            $this->assertSame('That slot is no longer available.', $e->errors()['availabilityId'][0]);
        }

        $this->assertDatabaseCount('appointments', 1);
        Mail::assertNothingSent();
    }

    public function test_a_slot_that_has_started_is_rejected(): void
    {
        $slot = Availability::factory()->create(['time_slot' => '09:00']);
        $this->travelTo($slot->startsAt()->addMinute());

        $this->expectException(ValidationException::class);

        $this->book(Agent::factory()->create(), Therapy::factory()->create(), $slot);
    }

    public function test_a_freed_slot_can_be_booked_again(): void
    {
        Mail::fake();
        $appointment = Appointment::factory()->create();
        $slot = $appointment->availability;
        $appointment->update(['status' => AppointmentStatus::Cancelled, 'availability_id' => null]);

        $rebooked = $this->book(Agent::factory()->create(), Therapy::factory()->create(), $slot);

        $this->assertSame($slot->id, $rebooked->availability_id);
        $this->assertDatabaseCount('appointments', 2);
    }
}
