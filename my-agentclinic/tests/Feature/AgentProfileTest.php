<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AgentProfileTest extends TestCase
{
    use RefreshDatabase;

    private function form(User $user)
    {
        return Volt::actingAs($user)->test('profile.update-profile-information-form');
    }

    public function test_agents_see_their_agent_fields_and_staff_do_not(): void
    {
        $agent = User::factory()->agent()->create();
        $agent->agent->update(['agent_type' => 'Planner', 'bio' => 'Plans before lunch.']);

        $this->actingAs($agent)->get('/profile')
            ->assertOk()
            ->assertSee('Agent type')
            ->assertSee('Plans before lunch.')
            ->assertSee('Your agent record and your session history stay with the clinic');

        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertDontSee('Agent type')
            ->assertDontSee('stay with the clinic');
    }

    public function test_the_form_starts_with_the_current_values(): void
    {
        $user = User::factory()->agent()->create(['name' => 'Pixel', 'email' => 'pixel@agents.test']);
        $user->agent->update(['agent_type' => 'Planner', 'bio' => 'Plans.']);

        $this->form($user)
            ->assertSet('name', 'Pixel')
            ->assertSet('email', 'pixel@agents.test')
            ->assertSet('agent_type', 'Planner')
            ->assertSet('bio', 'Plans.');
    }

    public function test_updating_changes_the_account_and_the_agent_record_together(): void
    {
        $user = User::factory()->agent()->create(['name' => 'Pixel', 'email' => 'pixel@agents.test']);

        $this->form($user)
            ->set('name', 'Pixel Prime')
            ->set('email', 'prime@agents.test')
            ->set('agent_type', 'Research agent')
            ->set('bio', 'Now with citations.')
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertDispatched('profile-updated');

        $user->refresh();
        $this->assertSame('Pixel Prime', $user->name);
        $this->assertSame('prime@agents.test', $user->email);
        $this->assertSame('Pixel Prime', $user->agent->name);
        $this->assertSame('prime@agents.test', $user->agent->email);
        $this->assertSame('Research agent', $user->agent->agent_type);
        $this->assertSame('Now with citations.', $user->agent->bio);
    }

    public function test_an_empty_bio_is_stored_as_null(): void
    {
        $user = User::factory()->agent()->create();
        $user->agent->update(['bio' => 'Something.']);

        $this->form($user)->set('bio', '')->call('updateProfileInformation')->assertHasNoErrors();

        $this->assertNull($user->agent->fresh()->bio);
    }

    public function test_confirmation_e_mails_follow_the_new_address(): void
    {
        $user = User::factory()->agent()->create(['email' => 'old@agents.test']);

        $this->form($user)->set('email', 'new@agents.test')->call('updateProfileInformation');

        $this->assertSame('new@agents.test', $user->agent->fresh()->email);
    }

    public function test_agent_fields_are_validated(): void
    {
        $user = User::factory()->agent()->create();
        $other = User::factory()->create(['email' => 'taken@agents.test']);

        $this->form($user)->set('agent_type', '')->call('updateProfileInformation')->assertHasErrors(['agent_type' => 'required']);
        $this->form($user)->set('bio', str_repeat('x', 1001))->call('updateProfileInformation')->assertHasErrors(['bio' => 'max']);
        $this->form($user)->set('email', $other->email)->call('updateProfileInformation')->assertHasErrors(['email' => 'unique']);
    }

    public function test_a_failed_update_changes_nothing(): void
    {
        $user = User::factory()->agent()->create(['name' => 'Pixel']);
        $user->agent->update(['agent_type' => 'Planner']);

        $this->form($user)
            ->set('name', 'Changed')
            ->set('agent_type', '')
            ->call('updateProfileInformation')
            ->assertHasErrors('agent_type');

        $this->assertSame('Pixel', $user->fresh()->name);
        $this->assertSame('Planner', $user->agent->fresh()->agent_type);
    }

    public function test_staff_profiles_have_no_agent_fields_to_validate(): void
    {
        $staff = User::factory()->create(['name' => 'Dr. Sam']);

        $this->form($staff)
            ->set('name', 'Dr. Samuel')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('Dr. Samuel', $staff->fresh()->name);
        $this->assertSame(0, \App\Models\Agent::count());
    }

    public function test_deleting_an_agent_account_keeps_the_record_and_its_history(): void
    {
        $user = User::factory()->agent()->create();
        $agent = $user->agent;
        $appointment = Appointment::factory()->create(['agent_id' => $agent->id]);

        Volt::actingAs($user)->test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull(User::find($user->id));
        $this->assertNotNull($agent->fresh());
        $this->assertNull($agent->fresh()->user_id);
        $this->assertTrue($appointment->fresh()->agent->is($agent));
    }
}
