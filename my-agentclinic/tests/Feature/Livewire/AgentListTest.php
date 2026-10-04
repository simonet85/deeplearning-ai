<?php

namespace Tests\Feature\Livewire;

use App\Models\Agent;
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

    public function test_it_shows_an_empty_state_without_agents(): void
    {
        Volt::test('agent-list')
            ->assertSee('waiting room is empty');
    }
}
