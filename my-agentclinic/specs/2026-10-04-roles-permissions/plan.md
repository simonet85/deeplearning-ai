# Plan: Roles and Permissions

Vertical slices, each with its tests. Run `sail test` after every slice.

## 1. Spatie, data model and migration
1. `composer require spatie/laravel-permission`; publish its config and migration; add `HasRoles` to `User`.
2. `App\Support\Access`: the 14 permissions with labels and groups, the default role-to-permission map, and an idempotent `sync()` / `ensureRole()`.
3. `RolesAndPermissionsSeeder` (calls `Access::sync()`), run first by `DatabaseSeeder`; `StaffSeeder` assigns roles.
4. Data migration: create the roles and permissions, give each user the Spatie role that matches the old `users.role`, then drop the column. Test it from a database that still has the old column.
5. Factories: `User::factory()` defaults to therapist, `admin()` and `agent()` states assign Spatie roles (creating the roles on the fly through `Access::ensureRole()`); reset the permission cache in the base `TestCase`.
6. Tests for `Access` (every default role has the right permissions, `sync()` is idempotent) and the migration.

## 2. Swap every role check for a permission check
1. Routes: `can:` middleware per page (staff pages by their `.view` permission, agent pages by `.use`); the dashboard sends users without `dashboard.view` but with `my-appointments.use` to their area, and everyone else gets a 403.
2. Components: therapies (`therapies.manage`), availability (`availability.manage-all` for the all-therapists view, `availability.manage` for slot owners and the pickers), appointments filter, photo controller (`dashboard.view`), navigation (`@can` instead of `isAgent()`), registration (`assignRole('agent')`).
3. Remove `EnsureUserHasRole`, its alias, the `Role`-enum cast and the `isAdmin()` / `isAgent()` / `hasRole()` overrides; keep `App\Enums\Role` only for the built-in role names.
4. Register `Authorize` as Livewire persistent middleware in place of `EnsureUserHasRole`.
5. Update every existing test to the new factories; the access matrix and isolation tests must pass unchanged in what they assert.

## 3. Roles page
1. `/admin/roles` behind `can:roles.manage`: list roles with their user counts and permissions grouped by domain.
2. Create a role, rename a custom role, tick and untick permissions, delete a custom role with no users.
3. Protections: built-in roles cannot be renamed or deleted; `admin` keeps `roles.manage` and `users.manage`.
4. Tests: every action, every protection, validation (unique name, length), and access (403 without the permission).

## 4. Users page
1. `/admin/users` behind `can:users.manage`: list accounts with name, e-mail, role and extra permissions.
2. Change a user's role, grant and revoke individual permissions, search by name or e-mail.
3. Protections: cannot change your own role, cannot remove `users.manage` from the last user who holds it; give an agent record to a user newly given an agent role.
4. Tests: every action and protection, the agent-record creation, access (403 without the permission).

## 5. Navigation, access matrix and docs
1. Nav links "Users" and "Roles" shown by `@can`, in the desktop and mobile menus.
2. Extend the route-by-role matrix with the new pages, add permission-level tests (a custom role with one permission reaches exactly one page; an extra permission on a user opens a page; revoking closes it), and a Livewire-request test for the new components.
3. README (roles, permissions and the two pages), `CHANGELOG.md`, `specs/roadmap.md`.

## 6. Verification
1. `sail test` and `sail composer test:coverage` (100%).
2. Browser walkthrough in Chrome and the responsive check (see `validation.md`).
3. Complete `validation.md` and merge into `main`.
