# Validation: Agent Accounts

The `agent-accounts` branch can merge when all of the following hold.

## Automated
- [ ] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [ ] `sail test` passes (PHPUnit). This is the merge gate.
- [ ] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [ ] Tests cover: registration (success, every validation rule, role forced to `agent`, no linking to an existing agent, rate limit), the shared booking action, the agent ailments page, the agent appointments page (book, list, cancel own), the profile (agent fields, e-mail sync, delete keeps history), and the staff pages still working for staff.

## Data isolation (automated)
- [ ] A route-by-role matrix passes: guests are redirected to login; agents get 403 on every staff page; staff get 403 on the agent pages; each role reaches its own pages.
- [ ] Agent A cannot see agent B's ailments or appointments, and cannot cancel or book into B's records, including with tampered ids sent to Livewire actions.
- [ ] No agent id is ever read from client input: it always comes from the signed-in user.

## Browser (automated, headed Chrome via Playwright, as for earlier steps)
- [ ] A new visitor registers as an agent (name, type, e-mail, password, confirmation), lands on their appointments page, and sees only their own data.
- [ ] The agent records an ailment, books an open slot, sees the appointment, and the confirmation e-mail appears in Mailpit (http://localhost:8025).
- [ ] The agent cancels the appointment and the slot is open again for staff.
- [ ] The agent edits their profile; the change shows on the agent's card in the staff dashboard.
- [ ] The agent cannot open `/dashboard`, `/ailments`, `/therapies`, `/availability` or `/appointments` (403).
- [ ] A second agent registers and cannot see the first agent's ailments or appointments.
- [ ] A staff member still sees and manages everything, including the new agents.
- [ ] No console errors on any page visited.

## Responsive
- [ ] The register page and both agent pages show no horizontal scroll at 320, 390, 768 and 1280px, and controls are at least 44px below `sm`.

## Hygiene
- [ ] `.env` is not committed.
- [ ] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [ ] No dead code (the 100% coverage gate enforces this).
