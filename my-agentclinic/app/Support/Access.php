<?php

namespace App\Support;

use App\Enums\Role as BuiltInRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The one place that lists every permission, what it is called in the UI, and what the built-in roles may do.
 * The seeder, the migration, the factories and the Roles and Users pages all read from here.
 */
final class Access
{
    public const GUARD = 'web';

    /**
     * Permission name => [label, group]. Groups are the domains shown on the Roles and Users pages.
     *
     * @var array<string, array{label: string, group: string}>
     */
    public const PERMISSIONS = [
        'dashboard.view' => ['label' => 'See the staff dashboard and other users\' photos', 'group' => 'Dashboard'],
        'ailments.view' => ['label' => 'Open the Ailments page', 'group' => 'Ailments'],
        'ailments.manage' => ['label' => 'Record ailments for any agent', 'group' => 'Ailments'],
        'therapies.view' => ['label' => 'Browse and rate therapies', 'group' => 'Therapies'],
        'therapies.manage' => ['label' => 'Add, edit and delete therapies', 'group' => 'Therapies'],
        'availability.view' => ['label' => 'Open the Availability page', 'group' => 'Availability'],
        'availability.manage' => ['label' => 'Manage your own availability slots', 'group' => 'Availability'],
        'availability.manage-all' => ['label' => 'Manage every therapist\'s slots', 'group' => 'Availability'],
        'appointments.view' => ['label' => 'Open the Appointments page and its report', 'group' => 'Appointments'],
        'appointments.manage' => ['label' => 'Book and cancel appointments for any agent', 'group' => 'Appointments'],
        'my-ailments.use' => ['label' => 'Record your own ailments (agent area)', 'group' => self::AGENT_GROUP],
        'my-appointments.use' => ['label' => 'Book and cancel your own sessions (agent area)', 'group' => self::AGENT_GROUP],
        'users.manage' => ['label' => 'Manage users, their roles and extra permissions', 'group' => 'Administration'],
        'roles.manage' => ['label' => 'Manage roles and their permissions', 'group' => 'Administration'],
    ];

    /** The group of permissions that only make sense for someone with an agent record. */
    public const AGENT_GROUP = 'Agent area';

    /**
     * The permissions each built-in role starts with. An administrator starts with every staff and administration
     * permission, but not the agent-area ones: those pages show the signed-in user's own agent record.
     * The administrator list is filled in by defaultPermissions().
     *
     * @var array<string, list<string>|'admin'>
     */
    public const ROLES = [
        'admin' => 'admin',
        'therapist' => [
            'dashboard.view',
            'ailments.view', 'ailments.manage',
            'therapies.view',
            'availability.view', 'availability.manage',
            'appointments.view', 'appointments.manage',
        ],
        'agent' => ['my-ailments.use', 'my-appointments.use'],
    ];

    /** Permissions the administrator role can never lose, so someone can always manage access. */
    public const ADMIN_LOCKED = ['roles.manage', 'users.manage'];

    /** @return list<string> */
    public static function permissionNames(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    /** @return list<string> every permission except the agent-area ones */
    public static function adminPermissions(): array
    {
        return array_keys(array_filter(self::PERMISSIONS, fn (array $meta) => $meta['group'] !== self::AGENT_GROUP));
    }

    /** @return list<string> the names of the roles that ship with the app and cannot be renamed or deleted */
    public static function builtInRoles(): array
    {
        return array_map(fn (BuiltInRole $role) => $role->value, BuiltInRole::cases());
    }

    public static function isBuiltIn(string $role): bool
    {
        return in_array($role, self::builtInRoles(), true);
    }

    /**
     * The permissions grouped by domain, for the checkbox lists: group => [name => label].
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::PERMISSIONS as $name => ['label' => $label, 'group' => $group]) {
            $groups[$group][$name] = $label;
        }

        return $groups;
    }

    /**
     * Create whatever is missing: every permission, and every built-in role with its default permissions.
     * Safe to run again at any time. A role that already exists keeps the permissions it has, so changes made
     * on the Roles page are never undone; a permission that is new is given to the built-in roles that include it
     * by default.
     */
    public static function sync(): void
    {
        $newPermissions = [];

        foreach (self::permissionNames() as $name) {
            if (Permission::findOrCreate($name, self::GUARD)->wasRecentlyCreated) {
                $newPermissions[] = $name;
            }
        }

        foreach (self::ROLES as $name => $defaults) {
            $defaults = $defaults === 'admin' ? self::adminPermissions() : $defaults;
            $role = Role::findOrCreate($name, self::GUARD);

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($defaults);
            } elseif ($given = array_values(array_intersect($newPermissions, $defaults))) {
                $role->givePermissionTo($given);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** The role with this name, after making sure the built-in roles and permissions exist. */
    public static function role(string $name): Role
    {
        if (! Role::where('name', $name)->where('guard_name', self::GUARD)->exists()) {
            self::sync();
        }

        return Role::findByName($name, self::GUARD);
    }
}
