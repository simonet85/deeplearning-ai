# Validation: Agent Accounts

The `agent-accounts` branch can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [x] `sail test` passes (PHPUnit): 306 tests, 981 assertions. This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] Tests cover: registration (success, every validation rule, role forced to `agent`, no linking to an existing agent, rate limit), the shared booking and cancel actions, the agent ailments page, the agent appointments page (book, list, cancel own), the profile (agent fields, e-mail sync, delete keeps history), and the staff pages still working for staff.

## Data isolation (automated)
- [x] A route-by-role matrix passes (`AccessMatrixTest`, 49 cases): guests are redirected to login; agents get 403 on every staff page; staff get 403 on the agent pages; each role reaches its own pages.
- [x] Agent A cannot see agent B's ailments or appointments, and cannot cancel B's appointment, including with a tampered id sent as a real Livewire update request (404, record untouched).
- [x] A staff component cannot be driven by an agent through a Livewire update request (403, with an administrator's identical request accepted as the control), and the agent components cannot be driven by staff.
- [x] No agent id is ever read from client input: the agent components have no `agentId` property, and setting one is rejected.

## Browser (automated, headed Chrome via Playwright, as for earlier steps)
Run on 2026-10-04: 71 checks, all passing on the final run.
- [x] A new visitor registers as an agent, a mismatched password confirmation is rejected, and they land on their appointments page seeing only their own data.
- [x] The agent records an ailment, books an open slot, sees the appointment, and the confirmation e-mail appears in Mailpit.
- [x] The agent cancels the appointment, the slot is open again, and it can be booked again.
- [x] The agent edits their profile; the change shows on the agent's card in the staff dashboard, with their e-mail.
- [x] The agent gets a 403 on `/ailments`, `/therapies`, `/availability` and `/appointments`, and `/dashboard` sends them to their area.
- [x] A second agent registers and sees none of the first agent's ailments or appointments, and cannot fetch the first agent's profile photo (403).
- [x] Staff still see and manage everything, including the new agent's ailment and appointment, and get a 403 on the agent pages.
- [x] No console errors on any page visited (the 403s the script provokes on purpose are ignored).

## Responsive
- [x] The register, login and both agent pages, plus the profile page, show no horizontal scroll at 320, 390, 768 and 1280px, and controls are at least 44px below `sm`. (Found and fixed during this run: the login and password-form inputs were 41px.)

## Profile photos (added during this step at the user's request)
- [x] Every user can add, replace and remove a profile photo; large images are resized to 800px and converted to WebP with Intervention Image; small ones are stored unchanged; the upload limit is 10 MB.
- [x] Photos are on a private disk and served only to their owner and to staff (an agent gets a 403 on another agent's photo, a guest is redirected to login).
- [x] The browser run uploaded the supplied `Image_.png` (768x1364, 1,677,077 bytes): it was stored as a 450x800 WebP of 28,660 bytes (2% of the original), shown in the navigation and on the agent's card on the staff dashboard, and served as `image/webp`.
- [x] Deleting an account deletes its photo file while the agent record and history stay.
- Found and fixed during this run: the compiled CSS predated the new classes, so the avatar had no size until the assets were rebuilt (`sail npm run build`).

## Hygiene
- [x] `.env` is not committed.
- [x] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [x] No dead code (the 100% coverage gate enforces this).

## Known gaps
- Registration does not verify the e-mail address (a deliberate default, see Open Questions in `requirements.md`).
- There is no way yet to merge a self-registered agent with an older staff-created record.
- The Docker `queue` service runs `queue:work`, so after changing job or mail code run `sail restart queue`.
