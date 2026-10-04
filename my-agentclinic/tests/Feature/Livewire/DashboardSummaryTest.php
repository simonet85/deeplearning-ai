<?php

namespace Tests\Feature\Livewire;

use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_zeroes_on_an_empty_clinic(): void
    {
        $component = Volt::test('dashboard-summary');

        $this->assertSame(0, $component->instance()->upcomingCount);
        $this->assertSame(0, $component->instance()->openSlotCount);
        $this->assertSame(0, $component->instance()->agentCount);
    }

    public function test_it_counts_upcoming_booked_sessions_open_slots_and_patients(): void
    {
        Appointment::factory()->count(2)->create();
        Appointment::factory()->cancelled()->create();
        $past = Appointment::factory()->create();
        $past->update(['datetime' => now()->subDay()]);
        Availability::factory()->count(3)->create();
        Agent::factory()->count(2)->create();

        $component = Volt::test('dashboard-summary')
            ->assertSee('Upcoming sessions')
            ->assertSee('Open slots')
            ->assertSee('Patients');

        $this->assertSame(2, $component->instance()->upcomingCount);
        $this->assertSame(3, $component->instance()->openSlotCount);
        // 4 agents come from the appointment factories, 2 more were created directly.
        $this->assertSame(6, $component->instance()->agentCount);
    }

    public function test_dashboard_page_includes_the_summary(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeLivewire('dashboard-summary');
    }
}
