<?php

namespace Tests\Feature\Livewire;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentBooked;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MyAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    private function as(User $user)
    {
        return Volt::actingAs($user)->test('my-appointments');
    }

    public function test_the_page_is_for_agents_only(): void
    {
        $this->get('/me/appointments')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/me/appointments')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/me/appointments')->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())
            ->get('/me/appointments')
            ->assertOk()
            ->assertSeeLivewire('my-appointments')
            ->assertSee('My appointments')
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false);
    }

    public function test_it_shows_an_empty_state(): void
    {
        $this->as(User::factory()->agent()->create())->assertSee('No sessions yet');
    }

    public function test_an_agent_books_an_open_slot_for_themselves(): void
    {
        Mail::fake();
        $user = User::factory()->agent()->create(['email' => 'pixel@agents.test']);
        $therapy = Therapy::factory()->create(['name' => 'Context Window Spa']);
        $slot = Availability::factory()->create(['time_slot' => '10:00']);

        $component = $this->as($user)
            ->set('therapyId', $therapy->id)
            ->set('availabilityId', $slot->id)
            ->call('book')
            ->assertHasNoErrors()
            ->assertSet('therapyId', null)
            ->assertSee('Context Window Spa')
            ->assertSee('Booked')
            ->assertDispatched('appointments-changed');

        $appointment = Appointment::sole();
        $this->assertSame($user->agent->id, $appointment->agent_id);
        $this->assertSame($slot->id, $appointment->availability_id);
        $this->assertTrue($component->instance()->openSlots->isEmpty());
        Mail::assertSent(AppointmentBooked::class, fn ($mail) => $mail->hasTo('pixel@agents.test'));
    }

    public function test_double_booking_is_rejected(): void
    {
        $taken = Appointment::factory()->create();

        $this->as(User::factory()->agent()->create())
            ->set('therapyId', Therapy::factory()->create()->id)
            ->set('availabilityId', $taken->availability_id)
            ->call('book')
            ->assertHasErrors('availabilityId');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_validates_its_fields(): void
    {
        $user = User::factory()->agent()->create();

        $this->as($user)->call('book')->assertHasErrors(['therapyId' => 'required', 'availabilityId' => 'required']);

        $this->as($user)
            ->set('therapyId', 999)
            ->set('availabilityId', 999)
            ->call('book')
            ->assertHasErrors(['therapyId' => 'exists', 'availabilityId' => 'exists']);
    }

    public function test_the_agent_cannot_be_chosen_from_the_client(): void
    {
        $this->expectException(PublicPropertyNotFoundException::class);

        $this->as(User::factory()->agent()->create())->set('agentId', 12345);
    }

    public function test_it_lists_only_the_agents_own_appointments_in_two_sections(): void
    {
        $mine = User::factory()->agent()->create();
        $upcoming = Appointment::factory()->create(['agent_id' => $mine->agent->id]);
        $past = Appointment::factory()->create(['agent_id' => $mine->agent->id]);
        $past->update(['datetime' => now()->subDays(3)]);
        $theirs = Appointment::factory()->create(['agent_id' => $this->otherAgent()->id]);

        $component = $this->as($mine)
            ->assertSee('Your upcoming sessions')
            ->assertSee('Your past sessions');

        $this->assertSame([$upcoming->id], $component->instance()->upcoming->pluck('id')->all());
        $this->assertSame([$past->id], $component->instance()->past->pluck('id')->all());
        $this->assertNotContains($theirs->id, $component->instance()->upcoming->pluck('id')->all());
    }

    public function test_the_past_section_is_hidden_when_empty(): void
    {
        $this->as(User::factory()->agent()->create())->assertDontSee('Your past sessions');
    }

    public function test_an_agent_cancels_their_own_appointment_and_frees_the_slot(): void
    {
        $user = User::factory()->agent()->create();
        $appointment = Appointment::factory()->create(['agent_id' => $user->agent->id]);
        $slotId = $appointment->availability_id;

        $component = $this->as($user)
            ->call('cancel', $appointment->id)
            ->assertSee('Cancelled')
            ->assertDispatched('appointments-changed');

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertTrue($component->instance()->openSlots->contains('id', $slotId));
    }

    public function test_an_agent_cannot_cancel_another_agents_appointment(): void
    {
        $other = Appointment::factory()->create(['agent_id' => $this->otherAgent()->id]);

        try {
            $this->as(User::factory()->agent()->create())->call('cancel', $other->id);
            $this->fail('Expected another agent\'s appointment to be unreachable.');
        } catch (ModelNotFoundException $e) {
            // In production this is a 404: the id is looked up among the agent's own appointments only.
            $this->assertSame(Appointment::class, $e->getModel());
        }

        $this->assertSame(AppointmentStatus::Booked, $other->fresh()->status);
        $this->assertNotNull($other->fresh()->availability_id);
    }

    public function test_the_cancel_button_and_reminder_marker_follow_the_appointment(): void
    {
        $user = User::factory()->agent()->create();
        Appointment::factory()->create(['agent_id' => $user->agent->id, 'reminder_sent_at' => now()->setTime(8, 30)]);

        $this->as($user)->assertSee('Reminder sent')->assertSee('wire:click="cancel', false);

        $cancelledOnly = User::factory()->agent()->create();
        Appointment::factory()->cancelled()->create(['agent_id' => $cancelledOnly->agent->id]);

        $this->as($cancelledOnly)->assertDontSee('wire:click="cancel', false)->assertDontSee('Reminder sent');
    }

    public function test_an_agent_account_without_a_record_is_refused(): void
    {
        Volt::actingAs(User::factory()->agentAccount()->create())
            ->test('my-appointments')
            ->assertForbidden();
    }

    private function otherAgent(): Agent
    {
        return User::factory()->agent()->create()->agent;
    }
}
