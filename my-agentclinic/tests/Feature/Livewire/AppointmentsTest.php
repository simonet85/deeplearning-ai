<?php

namespace Tests\Feature\Livewire;

use App\Enums\AppointmentStatus;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AppointmentsTest extends TestCase
{
    use RefreshDatabase;

    private function staff()
    {
        return Volt::actingAs(User::factory()->create())->test('appointments');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/appointments')->assertRedirect('/login');
    }

    public function test_staff_can_view_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/appointments')
            ->assertOk()
            ->assertSeeLivewire('appointments');
    }

    public function test_it_shows_an_empty_state(): void
    {
        $this->staff()->assertSee('No appointments yet');
    }

    public function test_form_lists_agents_therapies_and_only_open_future_slots(): void
    {
        Agent::factory()->create(['name' => 'Pixel']);
        Therapy::factory()->create(['name' => 'Context Window Spa']);
        $open = Availability::factory()->create(['therapist_id' => User::factory()->create(['name' => 'Dr. Open'])->id]);
        Appointment::factory()->create(); // booked slot
        Availability::factory()->create([
            'therapist_id' => User::factory()->create(['name' => 'Dr. Past'])->id,
            'date' => now()->subDay()->toDateString(),
        ]);

        $component = $this->staff()
            ->assertSee('Pixel')
            ->assertSee('Context Window Spa')
            ->assertSee('Dr. Open')
            ->assertDontSee('Dr. Past');

        $slots = $component->instance()->openSlots;
        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->is($open));
    }

    public function test_earlier_slots_today_are_not_offered(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        Availability::factory()->create(['date' => today()->toDateString(), 'time_slot' => '09:00']);
        $later = Availability::factory()->create(['date' => today()->toDateString(), 'time_slot' => '15:00']);

        $slots = $this->staff()->instance()->openSlots;

        $this->assertCount(1, $slots);
        $this->assertTrue($slots->first()->is($later));
    }

    public function test_it_books_an_appointment_and_consumes_the_slot(): void
    {
        $agent = Agent::factory()->create(['name' => 'Pixel']);
        $therapy = Therapy::factory()->create(['name' => 'Context Window Spa']);
        $slot = Availability::factory()->create(['time_slot' => '10:00']);

        $component = $this->staff()
            ->set('agentId', $agent->id)
            ->set('therapyId', $therapy->id)
            ->set('availabilityId', $slot->id)
            ->call('book')
            ->assertHasNoErrors()
            ->assertSet('agentId', null)
            ->assertSee('Pixel')
            ->assertSee('Booked');

        $this->assertTrue($component->instance()->openSlots->isEmpty());

        $appointment = Appointment::first();
        $this->assertSame($slot->therapist_id, $appointment->therapist_id);
        $this->assertSame($slot->id, $appointment->availability_id);
        $this->assertSame(AppointmentStatus::Booked, $appointment->status);
        $this->assertTrue($appointment->datetime->equalTo($slot->startsAt()));
    }

    public function test_double_booking_is_rejected(): void
    {
        $taken = Appointment::factory()->create();

        $this->staff()
            ->set('agentId', Agent::factory()->create()->id)
            ->set('therapyId', Therapy::factory()->create()->id)
            ->set('availabilityId', $taken->availability_id)
            ->call('book')
            ->assertHasErrors('availabilityId');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_a_slot_that_has_passed_is_rejected(): void
    {
        $slot = Availability::factory()->create(['time_slot' => '09:00']);
        $component = $this->staff()
            ->set('agentId', Agent::factory()->create()->id)
            ->set('therapyId', Therapy::factory()->create()->id)
            ->set('availabilityId', $slot->id);

        $this->travelTo($slot->startsAt()->addHour());

        $component->call('book')->assertHasErrors('availabilityId');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_cancelling_frees_the_slot_for_rebooking(): void
    {
        $appointment = Appointment::factory()->create();
        $slotId = $appointment->availability_id;

        $component = $this->staff()
            ->call('cancel', $appointment->id)
            ->assertSee('Cancelled');

        $this->assertTrue($component->instance()->openSlots->contains('id', $slotId));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertNull($appointment->availability_id);

        $component
            ->set('agentId', Agent::factory()->create()->id)
            ->set('therapyId', Therapy::factory()->create()->id)
            ->set('availabilityId', $slotId)
            ->call('book')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_cancelling_a_non_booked_appointment_changes_nothing(): void
    {
        $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Completed]);

        $this->staff()->call('cancel', $appointment->id);

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
        $this->assertNotNull($appointment->fresh()->availability_id);
    }

    public function test_cancel_button_only_shows_for_booked_appointments(): void
    {
        Appointment::factory()->cancelled()->create();

        $this->staff()->assertDontSee('wire:click="cancel');
    }

    public function test_appointments_are_listed_chronologically(): void
    {
        $later = Appointment::factory()->create();
        $later->update(['datetime' => now()->addDays(10)]);
        $sooner = Appointment::factory()->create();
        $sooner->update(['datetime' => now()->addDay()]);
        $later->agent->update(['name' => 'LaterAgent']);
        $sooner->agent->update(['name' => 'SoonerAgent']);

        $this->staff()->assertSeeInOrder(['SoonerAgent', 'LaterAgent']);
    }

    public function test_it_validates_required_and_unknown_ids(): void
    {
        $this->staff()
            ->call('book')
            ->assertHasErrors(['agentId' => 'required', 'therapyId' => 'required', 'availabilityId' => 'required']);

        $this->staff()
            ->set('agentId', 999)
            ->set('therapyId', 999)
            ->set('availabilityId', 999)
            ->call('book')
            ->assertHasErrors(['agentId' => 'exists', 'therapyId' => 'exists', 'availabilityId' => 'exists']);
    }

    public function test_relationships_and_status_cast(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertTrue($appointment->agent->appointments->first()->is($appointment));
        $this->assertTrue($appointment->availability->appointment->is($appointment));
        $this->assertNotNull($appointment->therapist);
        $this->assertNotNull($appointment->therapy);
        $this->assertSame(AppointmentStatus::Booked, $appointment->status);
    }
}
