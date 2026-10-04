# Requirements: Roles and Permissions (Spatie)

## Scope
Replace the hard-coded role check (`users.role` plus the `role:` middleware) with [spatie/laravel-permission](https://spatie.be/docs/laravel-permission), and let administrators manage roles and permissions from the app.

In scope:
- **Spatie replaces the old mechanism.** The three roles `admin`, `therapist` and `agent` become Spatie roles. Existing users are migrated and the `users.role` column is dropped.
- **Permissions by domain** (14, listed below), checked everywhere the app used to check a role.
- **A Roles page** (administrators only): create, rename and delete roles, and tick the permissions of each role.
- **A Users page** (administrators only): list accounts, change a user's role, and grant or revoke individual permissions on top of the role.
- **Safety rules** that stop an administrator from locking everyone out.

Out of scope: teams or multi-tenancy, per-record permissions (for example "only agent X"), permission history or audit log, wildcard permissions, API tokens.

## Permissions (by domain)
| Permission | Allows |
| --- | --- |
| `dashboard.view` | Staff dashboard; also lets a user see other users' profile photos |
| `ailments.view` / `ailments.manage` | Open the staff Ailments page / record ailments for any agent |
| `therapies.view` / `therapies.manage` | Browse the catalog and rate therapies / add, edit and delete therapies |
| `availability.view` / `availability.manage` | Open the Availability page / manage your own slots |
| `availability.manage-all` | Manage every therapist's slots and pick the therapist |
| `appointments.view` / `appointments.manage` | Open the staff Appointments page and report / book and cancel for any agent |
| `my-ailments.use` / `my-appointments.use` | The agent pages: record your own ailments / book and cancel your own sessions |
| `users.manage` | Open the Users page and change roles and individual permissions |
| `roles.manage` | Open the Roles page and change roles and their permissions |

Default roles (re-created by the seeder, and by a migration for existing data):
- **admin**: every permission.
- **therapist**: `dashboard.view`, `ailments.view`, `ailments.manage`, `therapies.view`, `availability.view`, `availability.manage`, `appointments.view`, `appointments.manage`. This matches what therapists can do today.
- **agent**: `my-ailments.use`, `my-appointments.use`. This matches what agents can do today.

## Decisions
- **Access is checked by permission, not by role name.** Routes use Laravel's `can:` middleware (Spatie registers each permission with the Gate), Livewire actions use `can()` / `abort_unless`, and views use `@can`. The old `EnsureUserHasRole` middleware and the `isAdmin()` / `isAgent()` helpers are removed; where the UI needs "can this user act as a therapist?" it asks for `availability.manage`.
- **One role per user** in the UI (a select). Spatie allows several, and the data model does not forbid it, but the Users page shows and sets a single role to keep it simple. Extra individual permissions can be added on top.
- **Built-in roles are protected.** `admin`, `therapist` and `agent` cannot be renamed or deleted. A role that still has users cannot be deleted.
- **Lock-out protection.** The `admin` role always keeps `roles.manage` and `users.manage` (those two boxes cannot be unticked for it), an administrator cannot change their own role, and the last user holding `users.manage` cannot lose it.
- **Agent pages need an agent record.** When a user is given a role that has `my-ailments.use` or `my-appointments.use` and has no agent record, one is created (name and e-mail from the account, agent type "Agent") so those pages work. Changing a user away from such a role leaves the record and its history in place.
- **Registration** gives new accounts the `agent` role through Spatie.
- **Photos**: the rule "staff can see everyone's photo" becomes "users with `dashboard.view`".
- **Who owns slots**: the therapist pickers (Availability and Appointments filter) list users who hold `availability.manage`, instead of users whose role is `therapist`.
- **Livewire**: the Gate middleware (`Illuminate\Auth\Middleware\Authorize`) is registered as persistent so permission checks also apply to Livewire update requests, as the role check did.
- **Permission names and labels** live in one class (`App\Support\Access`) used by the seeder, the migration, the factories and the UI, so they cannot drift apart.
- **Tests**: PHPUnit per `specs/tech-stack.md`; 100% line coverage of `app/`; `RefreshDatabase` and factories; the Spatie permission cache is reset between tests.
- **Responsive**: the new pages are mobile-first, use `<x-layout>` and `.touch-target`.
- **Tone**: witty and warm per `specs/mission.md`.

## Context
- Builds on Agent Accounts (`specs/2026-10-04-agent-accounts/`): the `role` middleware, `Role` enum, `isAdmin()`/`isAgent()` helpers, factory states and seeders all change.
- Spatie `laravel-permission` v6 is used with the default `web` guard and database cache.

## Data
- Spatie tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.
- `users.role` is dropped after its values are converted to Spatie roles.

## Open Questions
- Should deleting a role with users offer to reassign them? (Default: no, it is refused until the role is empty.)
