<?php

namespace Tests\Feature\Livewire;

use App\Enums\AppointmentStatus;
use App\Models\Agent;
use App\Models\Appointment;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AppointmentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_empty_states(): void
    {
        Agent::factory()->create();
        Therapy::factory()->create();

        Volt::test('appointment-report')
            ->assertSee('Nothing to report')
            ->assertSee('No therapies prescribed yet');
    }

    public function test_it_counts_appointments_by_agent_and_therapy_ignoring_cancelled(): void
    {
        $busy = Agent::factory()->create(['name' => 'BusyAgent']);
        $calm = Agent::factory()->create(['name' => 'CalmAgent']);
        $idle = Agent::factory()->create(['name' => 'IdleAgent']);
        $spa = Therapy::factory()->create(['name' => 'SpaTherapy']);
        $circle = Therapy::factory()->create(['name' => 'CircleTherapy']);

        Appointment::factory()->count(2)->create(['agent_id' => $busy->id, 'therapy_id' => $spa->id]);
        Appointment::factory()->create(['agent_id' => $calm->id, 'therapy_id' => $circle->id, 'status' => AppointmentStatus::Completed]);
        Appointment::factory()->cancelled()->create(['agent_id' => $idle->id, 'therapy_id' => $circle->id]);

        $component = Volt::test('appointment-report')
            ->assertSeeInOrder(['BusyAgent', '2', 'CalmAgent', '1'])
            ->assertDontSee('IdleAgent')
            ->assertSeeInOrder(['SpaTherapy', '2', 'CircleTherapy', '1']);

        $this->assertSame(2, $component->instance()->byAgent->first()->appointments_count);
        $this->assertSame(2, $component->instance()->byTherapy->first()->appointments_count);
    }

    public function test_report_updates_when_an_appointment_changes(): void
    {
        $component = Volt::test('appointment-report')->assertSee('Nothing to report');

        $appointment = Appointment::factory()->create();
        $appointment->agent->update(['name' => 'FreshAgent']);

        $component->dispatch('appointments-changed')->assertSee('FreshAgent');
    }

    public function test_appointments_page_includes_the_report(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/appointments')
            ->assertOk()
            ->assertSeeLivewire('appointment-report');
    }

    public function test_therapy_has_appointments_relationship(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertTrue($appointment->therapy->appointments->first()->is($appointment));
    }
}
