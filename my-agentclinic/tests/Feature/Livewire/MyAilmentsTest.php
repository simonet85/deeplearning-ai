<?php

namespace Tests\Feature\Livewire;

use App\Models\AgentAilment;
use App\Models\Ailment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MyAilmentsTest extends TestCase
{
    use RefreshDatabase;

    private function as(User $user)
    {
        return Volt::actingAs($user)->test('my-ailments');
    }

    public function test_the_page_is_for_agents_only(): void
    {
        $this->get('/me/ailments')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/me/ailments')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/me/ailments')->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())
            ->get('/me/ailments')
            ->assertOk()
            ->assertSeeLivewire('my-ailments')
            ->assertSee('My ailments')
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false);
    }

    public function test_it_shows_an_empty_state(): void
    {
        $this->as(User::factory()->agent()->create())->assertSee('Nothing on file');
    }

    public function test_an_agent_records_an_ailment_for_themselves(): void
    {
        $user = User::factory()->agent()->create();
        $ailment = Ailment::factory()->create(['name' => 'Token Fatigue']);

        $this->as($user)
            ->set('ailmentId', $ailment->id)
            ->set('severity', 4)
            ->set('notes', 'Rewrote it nine times.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Token Fatigue')
            ->assertSee('Rewrote it nine times.')
            ->assertSet('ailmentId', null)
            ->assertSet('notes', '');

        $this->assertDatabaseHas('agent_ailment', [
            'agent_id' => $user->agent->id,
            'ailment_id' => $ailment->id,
            'severity' => 4,
            'notes' => 'Rewrote it nine times.',
        ]);
    }

    public function test_notes_are_optional(): void
    {
        $user = User::factory()->agent()->create();

        $this->as($user)
            ->set('ailmentId', Ailment::factory()->create()->id)
            ->set('severity', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(AgentAilment::sole()->notes);
    }

    public function test_it_validates_the_form(): void
    {
        $user = User::factory()->agent()->create();
        $scaleOfThree = Ailment::factory()->create(['severity_scale' => 3]);

        $this->as($user)
            ->call('save')
            ->assertHasErrors(['ailmentId' => 'required', 'severity' => 'required']);

        $this->as($user)
            ->set('ailmentId', 999)
            ->set('severity', 1)
            ->call('save')
            ->assertHasErrors(['ailmentId' => 'exists']);

        $this->as($user)
            ->set('ailmentId', $scaleOfThree->id)
            ->set('severity', 4)
            ->call('save')
            ->assertHasErrors(['severity' => 'max']);

        $this->assertDatabaseCount('agent_ailment', 0);
    }

    public function test_an_agent_sees_only_their_own_ailments(): void
    {
        $mine = User::factory()->agent()->create();
        $other = User::factory()->agent()->create();
        $own = AgentAilment::factory()->create([
            'agent_id' => $mine->agent->id,
            'notes' => 'Mine alone.',
        ]);
        AgentAilment::factory()->create([
            'agent_id' => $other->agent->id,
            'notes' => 'Private to them.',
        ]);

        $component = $this->as($mine)
            ->assertSee('Mine alone.')
            ->assertDontSee('Private to them.');

        $this->assertSame([$own->id], $component->instance()->records->pluck('id')->all());
    }

    public function test_the_agent_cannot_be_chosen_from_the_client(): void
    {
        $this->expectException(PublicPropertyNotFoundException::class);

        $this->as(User::factory()->agent()->create())->set('agentId', 12345);
    }

    public function test_a_new_record_never_lands_on_another_agent(): void
    {
        $mine = User::factory()->agent()->create();
        $other = User::factory()->agent()->create();

        $this->as($mine)
            ->set('ailmentId', Ailment::factory()->create()->id)
            ->set('severity', 2)
            ->call('save');

        $this->assertSame(0, $other->agent->agentAilments()->count());
        $this->assertSame(1, $mine->agent->agentAilments()->count());
    }

    public function test_an_agent_account_without_a_record_is_refused(): void
    {
        $user = User::factory()->create(['role' => \App\Enums\Role::Agent]);

        Volt::actingAs($user)->test('my-ailments')->assertForbidden();
    }

    public function test_staff_pages_still_list_every_agents_ailments(): void
    {
        $agentUser = User::factory()->agent()->create();
        AgentAilment::factory()->create(['agent_id' => $agentUser->agent->id]);

        $this->assertSame(1, AgentAilment::count());
        Volt::actingAs(User::factory()->create())->test('ailments')->assertSee($agentUser->agent->name);
    }
}
