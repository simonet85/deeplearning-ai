# Plan: Agent Accounts

Vertical slices, each with its tests. Run `sail test` after every slice.

## 1. Role, data model and staff guard
1. Add `Role::Agent`; add `User::isAgent()` and an `agent()` relation; add `Agent::user()`.
2. Migration: `agents.user_id` nullable, unique, `nullOnDelete`. Add `user_id` to the `Agent` fillable list.
3. Factory states: `User::factory()->agent()` (creates a linked `Agent`), and `Agent::factory()->forUser()`.
4. Put every existing staff route behind `role:admin,therapist` (dashboard, ailments, therapies, availability, appointments). Keep `profile` for all roles.
5. Dashboard route: agents are redirected to their appointments page. Logged-in agents hitting a staff route get a 403.
6. Navigation: staff links for staff, agent links for agents (desktop and mobile menus).
7. Tests: each staff route returns 403 for an agent and still works for admins and therapists; the dashboard redirects agents; navigation shows the right links per role.

## 2. Self-registration
1. Reopen `/register` (Volt page `pages.auth.register`) for guests: name, agent type, e-mail, password, password confirmation, with the login page linking to it and back.
2. Create the `User` (role forced to `agent`) and a fresh `Agent` (name, type, e-mail, `user_id`) in one transaction, then log the user in and send them to their appointments page. Rate limit the route.
3. Update the existing "registration is not available" test; add tests for success, validation (required fields, duplicate e-mail, short or unconfirmed password), the role being forced to `agent` even if a role field is posted, no linking to an existing agent with the same e-mail, and the rate limit.

## 3. Shared booking action
1. Extract the booking transaction and the confirmation e-mail from `livewire/appointments.blade.php` into `App\Actions\BookAppointment` (`agent`, `therapy`, `slot` in; `Appointment` out; throws the same validation error when the slot is gone).
2. Use it from the staff component. All existing booking tests must pass unchanged.
3. Unit and feature tests for the action: books, rejects a taken or past slot, sends the confirmation only with an address.

## 4. Agent ailments page
1. `/me/ailments` behind `role:agent`, backed by a `my-ailments` Volt component: record an ailment (ailment, severity within its scale, notes) for the signed-in agent and list only their records.
2. Tests: records for self, validation, the list excludes other agents' records, the agent id cannot be overridden from the client.

## 5. Agent appointments page
1. `/me/appointments` behind `role:agent`, backed by a `my-appointments` Volt component: choose a therapy and an open slot to book for self (using `BookAppointment`), list own upcoming and past appointments, and cancel own booked ones (using the same rules as staff, with the "Reminder sent" marker).
2. Tests: books for self, double booking rejected, cancel own, cannot cancel another agent's appointment (403/not found), list excludes other agents' appointments, confirmation e-mail goes to the agent.

## 6. Profile
1. Extend the profile page for agents: name, agent type, bio and e-mail (kept in sync with `agents.email`) alongside the existing password and delete-account forms.
2. Deleting an agent account keeps the `Agent` record and its appointments (`user_id` becomes null).
3. Tests: edits update both `users` and `agents`, e-mail sync, staff profile unchanged, delete keeps history.

## 7. Isolation matrix
1. A single test class that walks every route as guest, agent, therapist and admin and asserts the expected status (redirect to login, 200, 403).
2. Cross-agent tests: agent A cannot see, edit, book into or cancel agent B's data through any Livewire action (tampered ids included).

## 8. Wrap-up
1. README (registration, roles, agent pages), `CHANGELOG.md`, and a roadmap line for agent self-service under Phase 2.
2. Run `sail test` and `sail composer test:coverage` (100%).
3. Browser walkthrough in Chrome and the responsive check (see `validation.md`).
4. Complete `validation.md` and merge into `main`.
