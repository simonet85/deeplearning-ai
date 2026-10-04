<?php

namespace Tests\Feature\Livewire;

use App\Models\Agent;
use App\Models\User;
use App\Support\Access;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    private function page(User $as)
    {
        return Volt::actingAs($as)->test('users');
    }

    private function role(string $name, array $permissions = []): Role
    {
        Access::sync();
        $role = Role::findOrCreate($name, 'web');
        $role->syncPermissions($permissions);

        return $role;
    }

    private function directOf(User $user): array
    {
        $names = $user->fresh()->getDirectPermissions()->pluck('name')->all();
        sort($names);

        return $names;
    }

    // ---- access ----

    public function test_only_users_who_can_manage_users_open_the_page(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->agent()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/users')
            ->assertOk()
            ->assertSeeLivewire('users')
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false);
    }

    public function test_a_therapist_given_the_permission_can_open_it(): void
    {
        $therapist = User::factory()->create();
        $therapist->givePermissionTo('users.manage');

        $this->actingAs($therapist)->get('/admin/users')->assertOk();
    }

    public function test_the_component_refuses_to_mount_without_the_permission(): void
    {
        Volt::actingAs(User::factory()->create())->test('users')->assertForbidden();
    }

    public function test_every_action_checks_the_permission_again(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $therapist = User::factory()->create();

        $actions = [
            fn ($page) => $page->call('changeRole', $target->id, 'admin'),
            fn ($page) => $page->call('togglePanel', $target->id),
            fn ($page) => $page->set('openId', $target->id)->set('extra', ['users.manage'])->call('saveExtra'),
        ];

        foreach ($actions as $action) {
            $page = $this->page($admin);
            $this->actingAs($therapist);

            $action($page)->assertForbidden();
        }

        $this->assertTrue($target->fresh()->hasExactRoles('therapist'));
        $this->assertSame([], $this->directOf($target));
    }

    // ---- listing and search ----

    public function test_it_lists_users_with_their_role_and_agent_badge(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Dr. Ada', 'email' => 'ada@clinic.test']);
        User::factory()->create(['name' => 'Dr. Sam']);
        User::factory()->agent()->create(['name' => 'Pixel']);

        $this->page($admin)
            ->assertSee('Dr. Ada')
            ->assertSee('ada@clinic.test')
            ->assertSee('(you)')
            ->assertSee('Dr. Sam')
            ->assertSee('Pixel')
            ->assertSee('Agent record')
            ->assertSeeInOrder(['Dr. Ada', 'Dr. Sam', 'Pixel']);
    }

    public function test_the_role_menu_offers_every_role_including_custom_ones(): void
    {
        $this->role('reception');
        $admin = User::factory()->admin()->create();

        $component = $this->page($admin)->assertSee('reception');

        $this->assertSame(['admin', 'therapist', 'agent', 'reception'], $component->instance()->roleNames->all());
    }

    public function test_a_user_without_a_role_shows_a_placeholder(): void
    {
        $admin = User::factory()->admin()->create();
        $nobody = User::factory()->create(['name' => 'Roleless']);
        $nobody->syncRoles([]);

        $this->page($admin)->assertSee('No role');
    }

    public function test_the_list_can_be_searched_by_name_or_e_mail(): void
    {
        $admin = User::factory()->admin()->create();
        $sam = User::factory()->create(['name' => 'Dr. Sam', 'email' => 'sam@clinic.test']);
        $riley = User::factory()->create(['name' => 'Dr. Riley', 'email' => 'riley@clinic.test']);

        $component = $this->page($admin)->set('search', 'RILEY');
        $this->assertSame([$riley->id], $component->instance()->users->pluck('id')->all());

        $component->set('search', 'sam@clinic');
        $this->assertSame([$sam->id], $component->instance()->users->pluck('id')->all());

        $component->set('search', 'nobody-here')->assertSee('No users match that search.');
        $this->assertCount(0, $component->instance()->users);
    }

    // ---- changing a role ----

    public function test_an_administrator_changes_a_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $reception = $this->role('reception', ['appointments.view']);

        $this->page($admin)->call('changeRole', $user->id, 'reception')->assertHasNoErrors();

        $this->assertTrue($user->fresh()->hasExactRoles('reception'));
        $this->actingAs($user->fresh())->get('/appointments')->assertOk();
        $this->actingAs($user->fresh())->get('/ailments')->assertForbidden();

        $this->page($admin)->call('changeRole', $user->id, 'admin');
        $this->assertTrue($user->fresh()->hasExactRoles('admin'));
    }

    public function test_a_role_that_does_not_exist_is_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->page($admin)->call('changeRole', $user->id, 'made-up')->assertHasErrors('user.'.$user->id);

        $this->assertTrue($user->fresh()->hasExactRoles('therapist'));
    }

    public function test_nobody_can_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create(); // another administrator, so this is not about the last one

        $this->page($admin)->call('changeRole', $admin->id, 'agent')->assertHasErrors('user.'.$admin->id);

        $this->assertTrue($admin->fresh()->hasExactRoles('admin'));
    }

    public function test_administrators_can_demote_each_other_but_never_themselves(): void
    {
        $first = User::factory()->admin()->create();
        $second = User::factory()->admin()->create();

        $this->page($first)->call('changeRole', $second->id, 'therapist')->assertHasNoErrors();
        $this->assertTrue($second->fresh()->hasExactRoles('therapist'));

        // Whoever acts holds users.manage and cannot edit themselves, so at least one manager always remains.
        $this->page($first)->call('changeRole', $first->id, 'therapist')->assertHasErrors('user.'.$first->id);
        $this->assertSame([$first->id], User::permission('users.manage')->pluck('users.id')->all());
    }

    // ---- agent records ----

    public function test_giving_a_staff_user_an_agent_role_creates_their_agent_record(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Dr. Sam', 'email' => 'sam@clinic.test']);

        $this->page($admin)->call('changeRole', $user->id, 'agent');

        $agent = $user->fresh()->agent;
        $this->assertNotNull($agent);
        $this->assertSame('Dr. Sam', $agent->name);
        $this->assertSame('sam@clinic.test', $agent->email);
        $this->assertSame('Agent', $agent->agent_type);
        $this->actingAs($user->fresh())->get('/me/appointments')->assertOk();
    }

    public function test_an_existing_agent_record_is_reused_and_kept_when_the_role_changes_away(): void
    {
        $admin = User::factory()->admin()->create();
        $agentUser = User::factory()->agent()->create();
        $recordId = $agentUser->agent->id;

        $this->page($admin)->call('changeRole', $agentUser->id, 'therapist');
        $this->assertSame($recordId, $agentUser->fresh()->agent->id);

        $this->page($admin)->call('changeRole', $agentUser->id, 'agent');
        $this->assertSame(1, Agent::count());
        $this->assertSame($recordId, $agentUser->fresh()->agent->id);
    }

    public function test_roles_without_agent_permissions_do_not_create_an_agent_record(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->agentAccount()->create();

        $this->page($admin)->call('changeRole', $user->id, 'therapist');

        $this->assertNull($user->fresh()->agent);
        $this->assertSame(0, Agent::count());
    }

    // ---- extra permissions ----

    public function test_the_panel_opens_with_the_users_individual_permissions_and_closes_again(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('therapies.manage');

        $this->page($admin)
            ->call('togglePanel', $user->id)
            ->assertSet('openId', $user->id)
            ->assertSet('extra', ['therapies.manage'])
            ->assertSee('Save permissions')
            ->assertSee('(from role)')
            ->call('togglePanel', $user->id)
            ->assertSet('openId', null)
            ->assertSet('extra', []);
    }

    public function test_individual_permissions_can_be_granted_and_revoked(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->actingAs($user)->get('/therapies/')->assertOk(); // therapists browse the catalog already
        $this->actingAs($user)->get('/admin/roles')->assertForbidden();

        $page = $this->page($admin)->call('togglePanel', $user->id);
        $page->set('extra', ['roles.manage', 'therapies.manage'])
            ->call('saveExtra')
            ->assertHasNoErrors()
            ->assertDispatched('permissions-saved', user: $user->id);

        $this->assertSame(['roles.manage', 'therapies.manage'], $this->directOf($user));
        $this->actingAs($user->fresh())->get('/admin/roles')->assertOk();

        $this->actingAs($admin); // back to the administrator after checking the other user's access
        $page->set('extra', ['therapies.manage'])->call('saveExtra');

        $this->assertSame(['therapies.manage'], $this->directOf($user));
        $this->actingAs($user->fresh())->get('/admin/roles')->assertForbidden();
    }

    public function test_unknown_permission_names_sent_from_the_client_are_ignored(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->page($admin)
            ->call('togglePanel', $user->id)
            ->set('extra', ['ailments.manage', 'made.up'])
            ->call('saveExtra');

        $this->assertSame(['ailments.manage'], $this->directOf($user));
        $this->assertDatabaseMissing('permissions', ['name' => 'made.up']);
    }

    public function test_nobody_can_change_their_own_individual_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->page($admin)
            ->call('togglePanel', $admin->id)
            ->set('extra', ['therapies.manage'])
            ->call('saveExtra')
            ->assertHasErrors('user.'.$admin->id);

        $this->assertSame([], $this->directOf($admin));
    }

    public function test_permissions_inherited_from_a_role_are_shown_but_not_editable(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $html = $this->page($admin)->call('togglePanel', $user->id)->html();

        $this->assertStringContainsString('checked disabled', $html);
    }

    public function test_giving_an_agent_permission_individually_creates_an_agent_record(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->page($admin)
            ->call('togglePanel', $user->id)
            ->set('extra', ['my-appointments.use'])
            ->call('saveExtra');

        $this->assertNotNull($user->fresh()->agent);
    }
}
