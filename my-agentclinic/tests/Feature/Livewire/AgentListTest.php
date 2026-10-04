<?php

namespace Tests\Feature\Livewire;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AgentListTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_agents_alphabetically(): void
    {
        Agent::factory()->create(['name' => 'Scout']);
        Agent::factory()->create(['name' => 'Atlas']);

        Volt::test('agent-list')
            ->assertSeeInOrder(['Atlas', 'Scout']);
    }

    public function test_it_shows_each_agents_type_and_bio(): void
    {
        Agent::factory()->create([
            'name' => 'Beacon',
            'agent_type' => 'Support bot',
            'bio' => 'Apologizes in advance.',
        ]);

        Volt::test('agent-list')
            ->assertSee('Beacon')
            ->assertSee('Support bot')
            ->assertSee('Apologizes in advance.');
    }

    public function test_guests_see_no_email_controls(): void
    {
        Agent::factory()->create(['email' => 'secret@agents.test']);

        Volt::test('agent-list')
            ->assertDontSee('Edit email')
            ->assertDontSee('secret@agents.test');
    }

    public function test_staff_see_each_agents_email_or_a_placeholder(): void
    {
        Agent::factory()->create(['name' => 'Pixel', 'email' => 'pixel@agents.test']);
        Agent::factory()->create(['name' => 'Scout', 'email' => null]);

        Volt::actingAs(User::factory()->create())->test('agent-list')
            ->assertSee('pixel@agents.test')
            ->assertSee('No email on file');
    }

    public function test_staff_can_set_and_clear_an_agents_email(): void
    {
        $agent = Agent::factory()->create(['email' => null]);
        $component = Volt::actingAs(User::factory()->create())->test('agent-list');

        $component->call('edit', $agent->id)
            ->assertSet('editingId', $agent->id)
            ->assertSet('email', '')
            ->set('email', 'new@agents.test')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editingId', null)
            ->assertSee('new@agents.test');
        $this->assertSame('new@agents.test', $agent->fresh()->email);

        $component->call('edit', $agent->id)
            ->assertSet('email', 'new@agents.test')
            ->set('email', '')
            ->call('save');
        $this->assertNull($agent->fresh()->email);
    }

    public function test_an_invalid_email_is_rejected_and_cancel_closes_the_form(): void
    {
        $agent = Agent::factory()->create(['email' => 'keep@agents.test']);

        Volt::actingAs(User::factory()->create())->test('agent-list')
            ->call('edit', $agent->id)
            ->set('email', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['email' => 'email'])
            ->call('cancel')
            ->assertSet('editingId', null)
            ->assertHasNoErrors();

        $this->assertSame('keep@agents.test', $agent->fresh()->email);
    }

    public function test_guests_cannot_edit_emails(): void
    {
        $agent = Agent::factory()->create();

        Volt::test('agent-list')->call('edit', $agent->id)->assertForbidden();
        Volt::test('agent-list')->call('save')->assertForbidden();
    }

    public function test_it_shows_an_empty_state_without_agents(): void
    {
        Volt::test('agent-list')
            ->assertSee('waiting room is empty');
    }
}
