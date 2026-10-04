# Validation: MVP Clinic Loop

The `mvp` branch can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [x] `sail test` passes (PHPUnit): 136 tests, 453 assertions. This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] Tests cover: ailments (list, create, validation), therapies (catalog, staff CRUD, role restrictions, pivot), ratings (create, update, average), availability (CRUD, ownership, duplicates), booking (happy path, double booking rejected, cancel frees slot), dashboard and report totals, and confirmation email (sent with address, skipped without, via `Mail::fake()`).
- [x] Feature tests assert the viewport meta, `touch-target` class and responsive grid classes on new pages (`MvpResponsiveTest`).

## Manual
Run in headed Chrome against the Sail app on 2026-10-04 with a Playwright script (76 checks, all passing on the final run), plus a look at screenshots.
- [x] End-to-end walkthrough: a therapist adds availability; admin records an ailment, creates, edits and rates a therapy, books an appointment for an agent, and the appointment shows on the appointments page and the dashboard tile. Booking took 3 selections plus 1 click. A second stale browser tab booking the same slot is rejected, and cancelling frees the slot so it can be booked again.
- [x] The confirmation email appears in the log mailer output (`To: pixel@agents.test`, `Hello, Pixel.`).
- [x] Reports show the correct counts by agent and by therapy and update live after booking and cancelling. (Found and fixed during this run: the report did not refresh until the page was reloaded.)
- [x] Guests are redirected to login on every new route; therapists cannot see the therapy add, edit or delete controls.
- [x] Responsive: dashboard, ailments, therapies, availability and appointments show no horizontal scroll at 320, 390, 768 and 1280px, and all controls are at least 44px at 320 and 390px. (Found and fixed during this run: the mobile menu button was 40px.)
- [x] Renders correctly in current Chrome with no console errors. (Found and fixed during this run: a Blade tag in the therapy form was malformed and threw a JS syntax error on the therapies page for admins.) Firefox and Safari are not required for the MVP.
- [ ] Dashboard loads in under 1s on seeded data. (Not measured.)
- [ ] Tone reads as witty and warm per `specs/mission.md`. (A human judgment; not assessed.)

## Hygiene
- [x] `.env` is not committed.
- [x] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` checkboxes are updated. (Phase 1 checkboxes in the roadmap were already unticked before this work and are left as they were.)
- [x] No dead code (the 100% coverage gate enforces this).

## Known gaps
- Agents have no UI to set an email address; confirmation emails only reach agents seeded with one.
- The availability page is a list, not a calendar view.
