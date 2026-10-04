# Requirements: Agent Accounts (self-service)

## Scope
Let agents sign themselves up and use the clinic without staff help. Today only staff log in and act on an agent's behalf (a decision from the Foundation and MVP specs). This step adds agent accounts alongside that, without removing anything from staff.

In scope:
- **Self-registration**: an agent creates their own account with a name, agent type, e-mail, password and password confirmation. No staff approval.
- **Agent area**, showing only the signed-in agent's own data:
  - Record their own ailments and see their history.
  - Book appointments for themselves, see their own appointments, and cancel their own booked ones.
  - Edit their profile: name, agent type, bio, e-mail and password.
- **Isolation**: agents cannot see or change another agent's data, and cannot open any staff page. Staff cannot open the agent pages.

Out of scope: browsing or rating the therapy catalog as an agent (the booking form lists therapies by name, which is enough), wellness scores, advanced reporting, audit logging, e-mail verification (see Open Questions), social login, API tokens, merging duplicate agent records.

## Decisions
- **Registration**: `/register` opens again, for agents only. The role is always `agent` and is set on the server, never read from the form. Registration is rate limited. The new user is signed in after registering.
- **Data model**: agent accounts live in the existing `users` table with a third role, `agent`. `agents` gains a nullable, unique `user_id`. Registration creates the `User` and a fresh `Agent` record in one transaction. A registration never links to an existing `Agent` record, even with the same e-mail: without e-mail verification that would let anyone claim another agent's record.
- **E-mail**: `agents.email` (used for confirmations and reminders) is kept equal to the user's e-mail, on registration and whenever the profile changes it.
- **Account deletion**: deleting an account removes the user and keeps the `Agent` record and its appointments for the clinic's history (`agents.user_id` is set to null).
- **Staff keep everything**: admins and therapists still act on behalf of any agent, exactly as in the MVP. Staff pages require the `admin` or `therapist` role.
- **Landing**: after login, staff go to the dashboard and agents go to their appointments page. The navigation shows staff links to staff and agent links to agents.
- **Separate components for agents**: the agent pages are their own Livewire components that take the agent from the signed-in user and never from request input, instead of adding role conditionals to the staff components. This keeps isolation simple to reason about and test.
- **Shared booking logic**: the atomic "book a slot" transaction moves from the staff appointments component into an action class used by both the staff and agent components, so the double-booking guarantee and the confirmation e-mail behave identically.
- **Cancel rules**: an agent can cancel only their own appointments with status `booked`.
- **Tone**: witty and warm, per `specs/mission.md`, including the registration page and empty states.
- **Testing**: PHPUnit per `specs/tech-stack.md`, 100% line coverage of `app/`, `RefreshDatabase` and factories, plus a route-by-role matrix and cross-agent data tests.
- **Responsive**: every new page is mobile-first and uses `<x-layout>`, `.touch-target` and the shared form components.

## Context
- Builds on the MVP and the reminders step (`specs/2026-10-04-mvp/`, `specs/2026-10-04-appointment-reminders/`).
- `Role` currently has `Admin` and `Therapist`; the `role` middleware alias (`EnsureUserHasRole`) already exists and accepts several roles.
- The Breeze Livewire auth stack is installed; registration was deliberately removed in Phase 1 and a test asserts `/register` is a 404. That test changes in this step.
- Mailpit catches e-mails in development, so booking confirmations and reminders for agent accounts can be checked at http://localhost:8025.

## Data
- `users.role`: new value `agent`.
- `agents.user_id`: nullable, unique foreign key to `users`, `nullOnDelete`.

## Open Questions
- Should registration require e-mail verification? (Default: no. Agents sign up with an e-mail and a confirmed password, as requested; verification can be added later with `MustVerifyEmail`.)
- Should staff be able to merge a self-registered agent with an older staff-created record? (Default: not in this step.)
