# Validation: Roles and Permissions

The `roles-permissions` branch can merge when all of the following hold.

## Automated
- [ ] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [ ] `sail test` passes (PHPUnit). This is the merge gate.
- [ ] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [ ] The data migration turns every old `users.role` value into the matching Spatie role and drops the column (tested from a database that still has it).
- [ ] The default roles hold exactly the permissions listed in `requirements.md`, and re-running the seeder changes nothing.
- [ ] Tests cover the Roles page (create, rename, tick and untick permissions, delete, every protection) and the Users page (change role, grant and revoke permissions, search, every protection, agent record creation).

## Access matrix and isolation (automated)
- [ ] The route-by-role matrix passes unchanged for guests, agents, therapists and administrators, and now includes `/admin/roles` and `/admin/users` (administrators only).
- [ ] Permission-level tests pass: a custom role with a single permission reaches exactly the matching page; an individual permission granted to a user opens a page and revoking it closes it; a user without `dashboard.view` cannot see other users' photos.
- [ ] The permission checks apply to Livewire update requests (a user without the permission gets a 403 on the Roles and Users components, with an administrator's identical request accepted as the control).
- [ ] No role name is hard-coded in an access check: a search of `app`, `routes` and `resources` finds no `isAdmin`, `isAgent` or `role:` middleware.

## Browser (automated, headed Chrome via Playwright)
- [ ] An administrator creates a role "Reception", ticks `appointments.view` only, and sees it on the Roles page.
- [ ] The administrator gives a user that role on the Users page; that user can then open Appointments and gets a 403 on Ailments, Therapies and Availability.
- [ ] Granting the user the individual permission `ailments.view` opens Ailments; revoking it closes it again.
- [ ] A built-in role cannot be renamed or deleted, the `admin` role keeps `roles.manage` and `users.manage`, and an administrator cannot change their own role.
- [ ] A role that still has users cannot be deleted; an empty custom role can.
- [ ] Giving a staff user the `agent` role creates their agent record and the agent pages work.
- [ ] A therapist and an agent still do everything they could before (spot check of each area).
- [ ] No console errors on any page visited.

## Responsive
- [ ] The Roles and Users pages show no horizontal scroll at 320, 390, 768 and 1280px, and controls are at least 44px below `sm`.

## Hygiene
- [ ] `.env` is not committed.
- [ ] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [ ] No dead code (the 100% coverage gate enforces this); `EnsureUserHasRole` and the `Role` cast are gone.
