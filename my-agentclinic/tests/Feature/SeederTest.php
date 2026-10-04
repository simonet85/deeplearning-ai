<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Agent;
use App\Models\Ailment;
use App\Models\Availability;
use App\Models\Therapy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_staff_and_agents(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', Role::Admin)->count());
        $this->assertSame(2, User::where('role', Role::Therapist)->count());
        $this->assertGreaterThanOrEqual(5, Agent::count());
        $this->assertGreaterThanOrEqual(3, Ailment::count());
        $this->assertGreaterThanOrEqual(3, Therapy::count());
        $this->assertTrue(Therapy::has('ailments')->exists());
        $this->assertSame(18, Availability::count());
    }
}
