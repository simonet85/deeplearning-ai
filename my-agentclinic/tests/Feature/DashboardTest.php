<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_staff_see_agents_on_the_dashboard(): void
    {
        Agent::factory()->create(['name' => 'Pixel']);
        Agent::factory()->create(['name' => 'Scout']);

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pixel')
            ->assertSee('Scout');
    }

    public function test_dashboard_shows_an_empty_state_without_agents(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('waiting room is empty');
    }
}
