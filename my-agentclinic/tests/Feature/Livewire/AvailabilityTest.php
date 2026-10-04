<?php

namespace Tests\Feature\Livewire;

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function tomorrow(): string
    {
        return now()->addDay()->toDateString();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/availability')->assertRedirect('/login');
    }

    public function test_staff_can_view_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/availability')
            ->assertOk()
            ->assertSeeLivewire('availability');
    }

    public function test_it_shows_an_empty_state(): void
    {
        Volt::actingAs(User::factory()->create())->test('availability')
            ->assertSee('No open slots');
    }

    public function test_therapist_sees_only_their_own_slots_in_order(): void
    {
        $me = User::factory()->create(['name' => 'Dr. Me']);
        $other = User::factory()->create(['name' => 'Dr. Other']);
        Availability::factory()->create(['therapist_id' => $me->id, 'date' => now()->addDays(2)->toDateString(), 'time_slot' => '09:00']);
        Availability::factory()->create(['therapist_id' => $me->id, 'date' => now()->addDay()->toDateString(), 'time_slot' => '15:00']);
        Availability::factory()->create(['therapist_id' => $other->id]);

        Volt::actingAs($me)->test('availability')
            ->assertSee('Dr. Me')
            ->assertDontSee('Dr. Other')
            ->assertSeeInOrder([now()->addDay()->format('D, M j'), now()->addDays(2)->format('D, M j')]);
    }

    public function test_admin_sees_all_slots_and_the_therapist_picker(): void
    {
        $therapist = User::factory()->create(['name' => 'Dr. Sam']);
        Availability::factory()->create(['therapist_id' => $therapist->id]);

        Volt::actingAs(User::factory()->admin()->create())->test('availability')
            ->assertSee('Dr. Sam')
            ->assertSee('Select a therapist');
    }

    public function test_therapist_adds_a_slot_for_themselves(): void
    {
        $me = User::factory()->create();

        Volt::actingAs($me)->test('availability')
            ->assertDontSee('Select a therapist')
            ->set('date', $this->tomorrow())
            ->set('timeSlot', '10:30')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('date', '')
            ->assertSee('10:30');

        $this->assertDatabaseHas('availability', ['therapist_id' => $me->id, 'time_slot' => '10:30']);
    }

    public function test_therapist_cannot_add_a_slot_for_someone_else_by_tampering(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        Volt::actingAs($me)->test('availability')
            ->set('therapistId', $other->id)
            ->set('date', $this->tomorrow())
            ->set('timeSlot', '10:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('availability', ['therapist_id' => $me->id]);
        $this->assertDatabaseMissing('availability', ['therapist_id' => $other->id]);
    }

    public function test_admin_adds_a_slot_for_a_chosen_therapist(): void
    {
        $therapist = User::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('availability')
            ->set('therapistId', $therapist->id)
            ->set('date', $this->tomorrow())
            ->set('timeSlot', '09:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('therapistId', null);

        $this->assertDatabaseHas('availability', ['therapist_id' => $therapist->id]);
    }

    public function test_admin_must_choose_a_real_therapist(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)->test('availability')
            ->set('date', $this->tomorrow())
            ->set('timeSlot', '09:00')
            ->call('save')
            ->assertHasErrors(['therapistId' => 'required']);

        Volt::actingAs($admin)->test('availability')
            ->set('therapistId', $admin->id)
            ->set('date', $this->tomorrow())
            ->set('timeSlot', '09:00')
            ->call('save')
            ->assertHasErrors(['therapistId' => 'exists']);
    }

    public function test_validation_rejects_missing_past_and_malformed_input(): void
    {
        $me = User::factory()->create();

        Volt::actingAs($me)->test('availability')
            ->call('save')
            ->assertHasErrors(['date' => 'required', 'timeSlot' => 'required']);

        Volt::actingAs($me)->test('availability')
            ->set('date', now()->subDay()->toDateString())
            ->set('timeSlot', '25:99')
            ->call('save')
            ->assertHasErrors(['date' => 'after_or_equal', 'timeSlot' => 'date_format']);

        $this->assertDatabaseCount('availability', 0);
    }

    public function test_today_is_allowed(): void
    {
        Volt::actingAs(User::factory()->create())->test('availability')
            ->set('date', now()->toDateString())
            ->set('timeSlot', '23:00')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_duplicate_slots_are_rejected_but_other_therapists_may_share_a_time(): void
    {
        $me = User::factory()->create();
        Availability::factory()->create(['therapist_id' => $me->id, 'date' => $this->tomorrow(), 'time_slot' => '09:00']);
        Availability::factory()->create(['date' => $this->tomorrow(), 'time_slot' => '10:00']);

        Volt::actingAs($me)->test('availability')
            ->set('date', $this->tomorrow())
            ->set('timeSlot', '09:00')
            ->call('save')
            ->assertHasErrors(['timeSlot' => 'unique']);

        Volt::actingAs($me)->test('availability')
            ->set('date', $this->tomorrow())
            ->set('timeSlot', '10:00')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_therapist_removes_their_own_slot(): void
    {
        $me = User::factory()->create();
        $slot = Availability::factory()->create(['therapist_id' => $me->id]);

        Volt::actingAs($me)->test('availability')
            ->call('remove', $slot->id)
            ->assertSee('No open slots');

        $this->assertDatabaseCount('availability', 0);
    }

    public function test_therapist_cannot_remove_someone_elses_slot(): void
    {
        $slot = Availability::factory()->create();

        Volt::actingAs(User::factory()->create())->test('availability')
            ->call('remove', $slot->id)
            ->assertForbidden();

        $this->assertDatabaseCount('availability', 1);
    }

    public function test_admin_can_remove_any_slot(): void
    {
        $slot = Availability::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('availability')
            ->call('remove', $slot->id);

        $this->assertDatabaseCount('availability', 0);
    }

    public function test_slots_are_grouped_under_one_heading_per_day(): void
    {
        $me = User::factory()->create();
        $day = now()->addDays(2);
        Availability::factory()->create(['therapist_id' => $me->id, 'date' => $day->toDateString(), 'time_slot' => '09:00']);
        Availability::factory()->create(['therapist_id' => $me->id, 'date' => $day->toDateString(), 'time_slot' => '14:00']);
        Availability::factory()->create(['therapist_id' => $me->id, 'date' => now()->addDays(4)->toDateString(), 'time_slot' => '10:00']);

        $html = Volt::actingAs($me)->test('availability')
            ->assertSee('Availability calendar')
            ->assertSeeInOrder([$day->format('D, M j'), '09:00', '14:00', now()->addDays(4)->format('D, M j'), '10:00'])
            ->html();

        $this->assertSame(1, substr_count($html, $day->format('D, M j')));
    }

    public function test_a_booked_slot_shows_a_badge_instead_of_a_remove_button(): void
    {
        $appointment = Appointment::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('availability')
            ->assertSee('Booked')
            ->assertDontSee('wire:click="remove');
    }

    public function test_a_booked_slot_cannot_be_removed(): void
    {
        $appointment = Appointment::factory()->create();

        Volt::actingAs(User::factory()->admin()->create())->test('availability')
            ->call('remove', $appointment->availability_id)
            ->assertSee('This slot has a booked appointment');

        $this->assertDatabaseCount('availability', 1);
    }

    public function test_relationships(): void
    {
        $slot = Availability::factory()->create();

        $this->assertTrue($slot->therapist->availability->first()->is($slot));
    }
}
