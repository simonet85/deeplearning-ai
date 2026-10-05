# Validation: Roles and Permissions

The `roles-permissions` branch can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [x] `sail test` passes (PHPUnit): 373 tests, 1,344 assertions. This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] The data migration turns every old `users.role` value into the matching Spatie role and drops the column, and can be reversed (tested from a database that still has the column).
- [x] The default roles hold exactly the permissions listed in `requirements.md`, re-running the seeder or `Access::sync()` changes nothing, never undoes changes made to a built-in role, and gives a permission that is added later to the roles that include it by default.
- [x] Tests cover the Roles page (create, rename, tick and untick permissions, delete, every protection, the lock-out rollback) and the Users page (change role, grant and revoke permissions, search, every protection, agent record creation).

## Access matrix and isolation (automated)
- [x] The route-by-role matrix passes for guests, agents, therapists and administrators, and includes `/admin/users` and `/admin/roles` (administrators only). 63 cases.
- [x] Permission-level tests pass: a custom role with a single permission reaches exactly the matching page; an individual permission granted to a user opens a page and revoking it closes it; a custom role with only `roles.manage` opens Roles but not Users; the Users and Roles links show only to those who may use them.
- [x] The permission checks apply to Livewire update requests: a user without the permission gets a 403 on the Roles and Users components, with an administrator's identical request accepted as the control, and every action re-checks the permission.
- [x] No role name is hard-coded in an access check: a search of `app`, `routes`, `resources` and `bootstrap` finds no `isAdmin`, `isAgent`, `role:` middleware or `where('role', ...)`, and `EnsureUserHasRole` is gone.

## Browser (automated, Chrome via Playwright)
Run on 2026-10-05: 47 checks, all passing on the final run. (An earlier attempt was stopped by the system because memory was critically low; the final run used headless Chrome to stay within it.)
- [x] An administrator creates a role "Reception", ticks `appointments.view` only, sees it persist after a reload, and renames it to "Front desk".
- [x] The administrator gives Sam that role on the Users page. Sam can open Appointments and sees only that link and no booking form; Sam gets a 403 on Ailments, Therapies, Availability, the dashboard, Users and Roles.
- [x] Granting Sam the individual permission `ailments.view` opens Ailments, Therapies stays closed, and revoking it closes Ailments again.
- [x] Built-in roles show no Rename or Delete button, the `admin` role shows `roles.manage` and `users.manage` ticked and disabled, a duplicate role name is rejected, and the administrator's own role menu is disabled.
- [x] A role that still has users cannot be deleted; once empty, the custom role can be.
- [x] Giving Riley the `agent` role creates their agent record and the agent pages work for Riley, while the staff pages close.
- [x] No console errors on any page visited.
- [ ] A therapist and an agent still do everything they could before. (Covered by the automated suite, which kept its assertions through the swap, and by the earlier agent walkthrough before the swap; not re-run in the browser after it.)

## Responsive
- [x] The Roles and Users pages show no horizontal scroll at 320, 390, 768, 1024 and 1280px, and controls are at least 44px at 320 and 390px. (Found and fixed during this run: with the two new links an administrator's navigation overflowed at 768px, so the menu now collapses to the mobile menu below 1024px.)

## Hygiene
- [x] `.env` is not committed.
- [x] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [x] No dead code (the 100% coverage gate enforces this); `EnsureUserHasRole` and the `Role` cast are gone. (The "last manager" guard drafted for the Users page was removed because it could never run: see `requirements.md`.)

## Known gaps
- `roles.manage` is effectively an administrator-level permission: someone holding it can give any role any permission, including to themselves through another role.
- Users have one role in the UI; Spatie allows several, and the Users page collapses to a single role when it changes one.
- Deleting a role with users is refused rather than offering to move them.
- The navigation now collapses to the mobile menu below 1024px, not 640px, for everyone.
