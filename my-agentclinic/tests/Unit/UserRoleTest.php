<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_admin_is_an_admin(): void
    {
        $user = User::factory()->admin()->make();

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->hasRole(Role::Admin));
    }

    public function test_therapist_is_not_an_admin(): void
    {
        $user = User::factory()->make();

        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->hasRole(Role::Therapist));
    }

    public function test_has_role_matches_any_of_the_given_roles(): void
    {
        $user = User::factory()->make();

        $this->assertTrue($user->hasRole(Role::Admin, Role::Therapist));
        $this->assertFalse($user->hasRole(Role::Admin));
    }
}
