<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use App\Support\Access;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function page(?User $as = null)
    {
        return Volt::actingAs($as ?? $this->admin())->test('roles');
    }

    private function role(string $name): Role
    {
        return Role::findByName($name);
    }

    private function custom(string $name = 'reception', array $permissions = []): Role
    {
        Access::sync();
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        return $role;
    }

    private function permissionsOf(Role $role): array
    {
        $names = $role->fresh()->permissions->pluck('name')->all();
        sort($names);

        return $names;
    }

    // ---- access ----

    public function test_only_users_who_can_manage_roles_open_the_page(): void
    {
        $this->get('/admin/roles')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/roles')->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())->get('/admin/roles')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/roles')
            ->assertOk()
            ->assertSeeLivewire('roles')
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false);
    }

    public function test_a_therapist_given_the_permission_can_open_it(): void
    {
        $therapist = User::factory()->create();
        $therapist->givePermissionTo('roles.manage');

        $this->actingAs($therapist)->get('/admin/roles')->assertOk();
    }

    public function test_the_component_refuses_to_mount_without_the_permission(): void
    {
        Volt::actingAs(User::factory()->create())->test('roles')->assertForbidden();
    }

    public function test_every_action_checks_the_permission_again(): void
    {
        $custom = $this->custom();
        $admin = $this->admin();
        $therapist = User::factory()->create();

        $actions = [
            fn ($page) => $page->set('newName', 'Sneaky')->call('createRole'),
            fn ($page) => $page->call('startRenaming', $custom->id),
            fn ($page) => $page->set('renamingId', $custom->id)->set('renameName', 'Sneaky')->call('renameRole'),
            fn ($page) => $page->set('selected.'.$custom->id, ['users.manage'])->call('savePermissions', $custom->id),
            fn ($page) => $page->call('deleteRole', $custom->id),
        ];

        foreach ($actions as $action) {
            // The page loads for an administrator, then the signed-in user turns out not to have the permission.
            $page = Volt::actingAs($admin)->test('roles');
            $this->actingAs($therapist);

            $action($page)->assertForbidden();
        }

        $this->assertSame('reception', $custom->fresh()->name);
        $this->assertSame([], $this->permissionsOf($custom));
        $this->assertDatabaseMissing('roles', ['name' => 'Sneaky']);
    }

    // ---- listing ----

    public function test_it_lists_the_roles_with_their_permissions_and_user_counts(): void
    {
        $this->custom('reception', ['appointments.view']);
        User::factory()->count(2)->create(); // two more therapists

        $component = $this->page()
            ->assertSee('Roles and their permissions')
            ->assertSee('reception')
            ->assertSee('Built-in')
            ->assertSee('Record ailments for any agent')
            ->assertSee('Administration');

        $selected = $component->get('selected');
        $reception = $this->role('reception');
        $this->assertSame(['appointments.view'], $selected[$reception->id]);
        $this->assertContains('roles.manage', $selected[$this->role('admin')->id]);
        $this->assertSame(2, $component->instance()->roles->firstWhere('name', 'therapist')->users_count);
    }

    // ---- creating ----

    public function test_an_administrator_adds_a_role(): void
    {
        $this->page()
            ->set('newName', 'Reception desk')
            ->call('createRole')
            ->assertHasNoErrors()
            ->assertSet('newName', '')
            ->assertSee('Reception desk');

        $role = $this->role('Reception desk');
        $this->assertSame('web', $role->guard_name);
        $this->assertSame([], $this->permissionsOf($role));
    }

    public function test_a_role_name_is_validated(): void
    {
        $this->page()->call('createRole')->assertHasErrors(['newName' => 'required']);
        $this->page()->set('newName', str_repeat('x', 51))->call('createRole')->assertHasErrors(['newName' => 'max']);
        $this->page()->set('newName', '<b>bold</b>')->call('createRole')->assertHasErrors(['newName' => 'regex']);
        $this->page()->set('newName', ' leading space')->call('createRole')->assertHasErrors(['newName' => 'regex']);
        $this->page()->set('newName', 'admin')->call('createRole')->assertHasErrors(['newName' => 'unique']);

        $this->custom('reception');
        $this->page()->set('newName', 'reception')->call('createRole')->assertHasErrors(['newName' => 'unique']);

        $this->assertSame(1 + 3, Role::count());
    }

    // ---- renaming ----

    public function test_a_custom_role_can_be_renamed(): void
    {
        $role = $this->custom('reception');

        $this->page()
            ->call('startRenaming', $role->id)
            ->assertSet('renamingId', $role->id)
            ->assertSet('renameName', 'reception')
            ->set('renameName', 'front desk')
            ->call('renameRole')
            ->assertHasNoErrors()
            ->assertSet('renamingId', null)
            ->assertSee('front desk');

        $this->assertSame('front desk', $role->fresh()->name);
    }

    public function test_keeping_the_same_name_is_allowed_and_cancel_closes_the_form(): void
    {
        $role = $this->custom('reception');

        $this->page()
            ->call('startRenaming', $role->id)
            ->call('renameRole')
            ->assertHasNoErrors();

        $this->page()
            ->call('startRenaming', $role->id)
            ->call('cancelRenaming')
            ->assertSet('renamingId', null)
            ->assertSet('renameName', '');
    }

    public function test_renaming_validates_the_name(): void
    {
        $role = $this->custom('reception');
        $this->custom('billing');

        $this->page()->call('startRenaming', $role->id)->set('renameName', '')->call('renameRole')->assertHasErrors(['renameName' => 'required']);
        $this->page()->call('startRenaming', $role->id)->set('renameName', 'billing')->call('renameRole')->assertHasErrors(['renameName' => 'unique']);
        $this->page()->call('startRenaming', $role->id)->set('renameName', '!!')->call('renameRole')->assertHasErrors(['renameName' => 'regex']);

        $this->assertSame('reception', $role->fresh()->name);
    }

    public function test_built_in_roles_cannot_be_renamed(): void
    {
        Access::sync();

        foreach (Access::builtInRoles() as $name) {
            $role = $this->role($name);

            $this->page()
                ->call('startRenaming', $role->id)
                ->assertSet('renamingId', null)
                ->assertHasErrors('role.'.$role->id);
        }

        // Even with the form state forced from the client, the server refuses.
        $admin = $this->role('admin');
        $this->page()
            ->set('renamingId', $admin->id)
            ->set('renameName', 'boss')
            ->call('renameRole')
            ->assertForbidden();

        $this->assertSame('admin', $admin->fresh()->name);
    }

    // ---- permissions ----

    public function test_ticking_and_unticking_permissions_saves_them(): void
    {
        $role = $this->custom('reception', ['appointments.view']);

        $this->page()
            ->set('selected.'.$role->id, ['appointments.view', 'ailments.view', 'therapies.view'])
            ->call('savePermissions', $role->id)
            ->assertDispatched('permissions-saved', role: $role->id);

        $this->assertSame(['ailments.view', 'appointments.view', 'therapies.view'], $this->permissionsOf($role));

        $this->page()
            ->set('selected.'.$role->id, ['ailments.view'])
            ->call('savePermissions', $role->id);

        $this->assertSame(['ailments.view'], $this->permissionsOf($role));

        $this->page()->set('selected.'.$role->id, [])->call('savePermissions', $role->id);

        $this->assertSame([], $this->permissionsOf($role));
    }

    public function test_the_change_takes_effect_for_users_straight_away(): void
    {
        $role = $this->custom('reception', ['appointments.view']);
        $user = User::factory()->create();
        $user->syncRoles($role);
        $this->actingAs($user)->get('/ailments')->assertForbidden();

        $this->page()->set('selected.'.$role->id, ['appointments.view', 'ailments.view'])->call('savePermissions', $role->id);

        $this->actingAs($user->fresh())->get('/ailments')->assertOk();
    }

    public function test_unknown_permission_names_sent_from_the_client_are_ignored(): void
    {
        $role = $this->custom('reception');

        $this->page()
            ->set('selected.'.$role->id, ['ailments.view', 'made.up', 'DROP TABLE'])
            ->call('savePermissions', $role->id);

        $this->assertSame(['ailments.view'], $this->permissionsOf($role));
        $this->assertDatabaseMissing('permissions', ['name' => 'made.up']);
    }

    public function test_the_administrator_role_always_keeps_the_permissions_that_manage_access(): void
    {
        Access::sync();
        $admin = $this->role('admin');

        $this->page()
            ->set('selected.'.$admin->id, ['ailments.view'])
            ->call('savePermissions', $admin->id);

        $this->assertSame(['ailments.view', 'roles.manage', 'users.manage'], $this->permissionsOf($admin));
    }

    public function test_built_in_roles_other_than_admin_can_be_changed(): void
    {
        Access::sync();
        $therapist = $this->role('therapist');

        $this->page()
            ->set('selected.'.$therapist->id, ['dashboard.view'])
            ->call('savePermissions', $therapist->id);

        $this->assertSame(['dashboard.view'], $this->permissionsOf($therapist));
    }

    // ---- deleting ----

    public function test_an_empty_custom_role_can_be_deleted(): void
    {
        $role = $this->custom('reception');

        $this->page()->call('deleteRole', $role->id)->assertHasNoErrors()->assertDontSee('reception');

        $this->assertNull(Role::where('name', 'reception')->first());
    }

    public function test_a_role_that_still_has_users_cannot_be_deleted(): void
    {
        $role = $this->custom('reception');
        User::factory()->create()->syncRoles($role);

        $this->page()
            ->call('deleteRole', $role->id)
            ->assertHasErrors('role.'.$role->id)
            ->assertSee('This role still has 1 user');

        $this->assertNotNull($role->fresh());

        User::factory()->create()->syncRoles($role);
        $this->page()->call('deleteRole', $role->id)->assertSee('This role still has 2 users');
    }

    public function test_built_in_roles_cannot_be_deleted(): void
    {
        Access::sync();

        foreach (Access::builtInRoles() as $name) {
            $role = $this->role($name);

            $this->page()->call('deleteRole', $role->id)->assertHasErrors('role.'.$role->id);
            $this->assertNotNull($role->fresh());
        }
    }

    public function test_deleting_the_role_being_renamed_closes_the_rename_form(): void
    {
        $role = $this->custom('reception');

        $this->page()
            ->call('startRenaming', $role->id)
            ->call('deleteRole', $role->id)
            ->assertSet('renamingId', null);
    }

    public function test_deleting_another_role_leaves_the_rename_form_open(): void
    {
        $renaming = $this->custom('reception');
        $other = $this->custom('billing');

        $this->page()
            ->call('startRenaming', $renaming->id)
            ->call('deleteRole', $other->id)
            ->assertSet('renamingId', $renaming->id);
    }
}
