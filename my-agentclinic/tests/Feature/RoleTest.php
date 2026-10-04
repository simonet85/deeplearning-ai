<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])->get('/_admin-only', fn () => 'ok');
    }

    public function test_users_default_to_the_therapist_role(): void
    {
        $this->assertSame(Role::Therapist, User::factory()->create()->fresh()->role);
    }

    public function test_admin_can_reach_admin_only_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/_admin-only')
            ->assertOk();
    }

    public function test_therapist_is_forbidden_from_admin_only_routes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/_admin-only')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_before_the_role_check(): void
    {
        $this->get('/_admin-only')->assertRedirect('/login');
    }
}
