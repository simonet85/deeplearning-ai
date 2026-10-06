<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Access;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    private const THERAPIST = [
        'dashboard.view',
        'ailments.view', 'ailments.manage',
        'therapies.view',
        'availability.view', 'availability.manage',
        'appointments.view', 'appointments.manage',
    ];

    /** The administrator starts with everything except the two agent-area permissions. */
    private const ADMIN = [
        'dashboard.view', 'ailments.view', 'ailments.manage', 'therapies.view', 'therapies.manage',
        'availability.view', 'availability.manage', 'availability.manage-all',
        'appointments.view', 'appointments.manage', 'users.manage', 'roles.manage',
    ];

    private const ALL = [
        'dashboard.view', 'ailments.view', 'ailments.manage', 'therapies.view', 'therapies.manage',
        'availability.view', 'availability.manage', 'availability.manage-all',
        'appointments.view', 'appointments.manage', 'my-ailments.use', 'my-appointments.use',
        'users.manage', 'roles.manage',
    ];

    private function permissionsOf(string $role): array
    {
        $names = Role::findByName($role)->permissions->pluck('name')->all();
        sort($names);

        return $names;
    }

    private function sorted(array $names): array
    {
        sort($names);

        return $names;
    }

    public function test_the_default_roles_hold_exactly_the_documented_permissions(): void
    {
        Access::sync();

        $this->assertSame($this->sorted(self::ADMIN), $this->permissionsOf('admin'));
        $this->assertSame($this->sorted(self::THERAPIST), $this->permissionsOf('therapist'));
        $this->assertSame(['my-ailments.use', 'my-appointments.use'], $this->permissionsOf('agent'));
        $this->assertSame(self::ALL, Access::permissionNames());
        $this->assertSame(14, Permission::count());
        $this->assertSame(3, Role::count());
    }

    public function test_syncing_again_changes_nothing(): void
    {
        Access::sync();
        $before = DB::table('role_has_permissions')->count();

        Access::sync();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame($before, DB::table('role_has_permissions')->count());
        $this->assertSame(14, Permission::count());
        $this->assertSame(3, Role::count());
    }

    public function test_syncing_never_undoes_changes_made_to_a_built_in_role(): void
    {
        Access::sync();
        Role::findByName('therapist')->revokePermissionTo('appointments.manage');
        Role::findByName('agent')->givePermissionTo('ailments.view');

        Access::sync();

        $this->assertNotContains('appointments.manage', $this->permissionsOf('therapist'));
        $this->assertContains('ailments.view', $this->permissionsOf('agent'));
    }

    public function test_a_permission_added_later_is_given_to_the_administrator_role(): void
    {
        Access::sync();
        Permission::findByName('roles.manage')->delete();
        $this->assertNotContains('roles.manage', $this->permissionsOf('admin'));

        Access::sync();

        $this->assertContains('roles.manage', $this->permissionsOf('admin'));
        $this->assertNotContains('roles.manage', $this->permissionsOf('therapist'));
    }

    public function test_a_new_agent_area_permission_is_not_given_to_the_administrator_role(): void
    {
        Access::sync();
        Permission::findByName('my-ailments.use')->delete();

        Access::sync();

        $this->assertNotContains('my-ailments.use', $this->permissionsOf('admin'));
        $this->assertContains('my-ailments.use', $this->permissionsOf('agent'));
    }

    public function test_role_creates_the_built_in_roles_on_demand_and_reuses_them(): void
    {
        // The migration already created them; start from nothing.
        Role::query()->delete();
        Permission::query()->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertSame(0, Role::count());

        $first = Access::role('agent');

        $this->assertSame(3, Role::count());
        $this->assertTrue($first->is(Access::role('agent')));
        $this->assertSame(3, Role::count());
    }

    public function test_the_can_middleware_checks_the_permission_not_a_role_name(): void
    {
        Route::middleware(['web', 'auth', 'can:users.manage'])->get('/_users-only', fn () => 'ok');

        $this->actingAs(User::factory()->admin()->create())->get('/_users-only')->assertOk();
        $this->actingAs(User::factory()->create())->get('/_users-only')->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())->get('/_users-only')->assertForbidden();

        // An individual permission opens it for a therapist, and revoking it closes it again.
        $therapist = User::factory()->create();
        $therapist->givePermissionTo('users.manage');
        $this->actingAs($therapist)->get('/_users-only')->assertOk();

        $therapist->revokePermissionTo('users.manage');
        $this->actingAs($therapist->fresh())->get('/_users-only')->assertForbidden();
    }

    public function test_a_custom_role_with_one_permission_reaches_exactly_that_page(): void
    {
        $role = Role::create(['name' => 'reception', 'guard_name' => 'web']);
        $role->givePermissionTo('appointments.view');
        $user = User::factory()->create();
        $user->syncRoles($role);

        $this->actingAs($user)->get('/appointments')->assertOk();
        $this->actingAs($user)->get('/ailments')->assertForbidden();
        $this->actingAs($user)->get('/therapies')->assertForbidden();
        $this->actingAs($user)->get('/availability')->assertForbidden();
        $this->actingAs($user)->get('/me/appointments')->assertForbidden();
        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_a_user_who_can_only_use_the_agent_area_is_sent_there_from_the_dashboard(): void
    {
        $this->actingAs(User::factory()->agent()->create())->get('/dashboard')->assertRedirect(route('agent.home'));
    }

    public function test_staff_without_view_permission_cannot_act_through_the_page_actions(): void
    {
        // The buttons are hidden without the permission, and the actions refuse too.
        $viewOnly = Role::create(['name' => 'observer', 'guard_name' => 'web']);
        $viewOnly->givePermissionTo(['appointments.view', 'ailments.view', 'availability.view', 'therapies.view']);
        $user = User::factory()->create();
        $user->syncRoles($viewOnly);

        $this->actingAs($user)->get('/appointments')->assertOk()->assertDontSee('Book appointment');
        $this->actingAs($user)->get('/ailments')->assertOk()->assertDontSee('Record ailment');
        $this->actingAs($user)->get('/availability')->assertOk()->assertDontSee('Add slot');
    }

    // ---- the migration that moves roles out of users.role ----

    public function test_the_migration_converts_old_role_values_and_drops_the_column(): void
    {
        $migration = require database_path('migrations/2026_10_04_222542_convert_user_roles_to_permission_roles.php');

        $admin = User::factory()->create();
        $therapist = User::factory()->create();
        $agent = User::factory()->create();
        foreach ([$admin, $therapist, $agent] as $user) {
            $user->syncRoles([]);
        }
        DB::table('roles')->delete();
        DB::table('permissions')->delete();

        // Put the old column back, as it was before this migration ran.
        Schema::table('users', fn ($table) => $table->string('role')->default('therapist'));
        DB::table('users')->where('id', $admin->id)->update(['role' => 'admin']);
        DB::table('users')->where('id', $therapist->id)->update(['role' => 'therapist']);
        DB::table('users')->where('id', $agent->id)->update(['role' => 'agent']);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertTrue($admin->fresh()->hasExactRoles('admin'));
        $this->assertTrue($therapist->fresh()->hasExactRoles('therapist'));
        $this->assertTrue($agent->fresh()->hasExactRoles('agent'));
        $this->assertTrue($admin->fresh()->can('roles.manage'));
        $this->assertFalse($admin->fresh()->can('my-appointments.use'));
        $this->assertTrue($agent->fresh()->can('my-appointments.use'));
        $this->assertFalse($agent->fresh()->can('dashboard.view'));
    }

    public function test_the_migration_can_be_reversed(): void
    {
        $migration = require database_path('migrations/2026_10_04_222542_convert_user_roles_to_permission_roles.php');
        $admin = User::factory()->admin()->create();
        $nobody = User::factory()->create();
        $nobody->syncRoles([]);

        $migration->down();

        $this->assertTrue(Schema::hasColumn('users', 'role'));
        $this->assertSame('admin', DB::table('users')->where('id', $admin->id)->value('role'));
        $this->assertSame('therapist', DB::table('users')->where('id', $nobody->id)->value('role'));
    }
}
