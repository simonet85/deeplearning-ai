<?php

namespace Tests\Feature\Livewire;

use App\Models\Agent;
use App\Models\AgentAilment;
use App\Models\Ailment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AilmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/ailments')->assertRedirect('/login');
    }

    public function test_staff_can_view_the_page_with_navigation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/ailments')
            ->assertOk()
            ->assertSeeLivewire('ailments')
            ->assertSee('Ailments');
    }

    public function test_it_shows_an_empty_state(): void
    {
        Volt::test('ailments')->assertSee('No ailments on file');
    }

    public function test_it_lists_agents_alphabetically_in_the_form(): void
    {
        Agent::factory()->create(['name' => 'Scout']);
        Agent::factory()->create(['name' => 'Atlas']);

        Volt::test('ailments')->assertSeeInOrder(['Atlas', 'Scout']);
    }

    public function test_it_records_an_ailment_for_an_agent(): void
    {
        $agent = Agent::factory()->create(['name' => 'Pixel']);
        $ailment = Ailment::factory()->create(['name' => 'Token Fatigue']);

        Volt::test('ailments')
            ->set('agentId', $agent->id)
            ->set('ailmentId', $ailment->id)
            ->set('severity', 4)
            ->set('notes', 'Rewrote it nine times.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Rewrote it nine times.')
            ->assertSet('agentId', null)
            ->assertSet('notes', '');

        $this->assertDatabaseHas('agent_ailment', [
            'agent_id' => $agent->id,
            'ailment_id' => $ailment->id,
            'severity' => 4,
            'notes' => 'Rewrote it nine times.',
        ]);
    }

    public function test_notes_are_optional(): void
    {
        $agent = Agent::factory()->create();
        $ailment = Ailment::factory()->create();

        Volt::test('ailments')
            ->set('agentId', $agent->id)
            ->set('ailmentId', $ailment->id)
            ->set('severity', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(AgentAilment::first()->notes);
    }

    public function test_it_validates_required_fields(): void
    {
        Volt::test('ailments')
            ->call('save')
            ->assertHasErrors(['agentId' => 'required', 'ailmentId' => 'required', 'severity' => 'required']);

        $this->assertDatabaseCount('agent_ailment', 0);
    }

    public function test_severity_must_fit_the_ailments_scale(): void
    {
        $agent = Agent::factory()->create();
        $ailment = Ailment::factory()->create(['severity_scale' => 3]);

        Volt::test('ailments')
            ->set('agentId', $agent->id)
            ->set('ailmentId', $ailment->id)
            ->set('severity', 4)
            ->call('save')
            ->assertHasErrors(['severity' => 'max']);
    }

    public function test_it_rejects_unknown_agents_and_ailments(): void
    {
        Volt::test('ailments')
            ->set('agentId', 999)
            ->set('ailmentId', 999)
            ->set('severity', 1)
            ->call('save')
            ->assertHasErrors(['agentId' => 'exists', 'ailmentId' => 'exists']);
    }

    public function test_model_relationships(): void
    {
        $record = AgentAilment::factory()->create();

        $this->assertCount(1, $record->agent->agentAilments);
        $this->assertCount(1, $record->ailment->agentAilments);
        $this->assertTrue($record->ailment->agentAilments->first()->is($record));
    }
}
