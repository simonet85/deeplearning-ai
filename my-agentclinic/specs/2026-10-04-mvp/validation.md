# Validation: MVP Clinic Loop

The `mvp` branch can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [x] `sail test` passes (PHPUnit): 132 tests, 446 assertions. This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] Tests cover: ailments (list, create, validation), therapies (catalog, staff CRUD, role restrictions, pivot), ratings (create, update, average), availability (CRUD, ownership, duplicates), booking (happy path, double booking rejected, cancel frees slot), dashboard and report totals, and confirmation email (sent with address, skipped without, via `Mail::fake()`).
- [x] Feature tests assert the viewport meta, `touch-target` class and responsive grid classes on new pages (`MvpResponsiveTest`).

## Manual
Not yet done: these need a person in a browser.
- [ ] End-to-end walkthrough: log in as a therapist and add availability; log in as admin, record an ailment for an agent, book an appointment for that agent in under 5 clicks; the appointment shows on the dashboard; the confirmation email appears in the log mailer output; attempting to book the same slot again is rejected; cancelling frees the slot. (Each step is covered by automated tests; the real-browser flow and click count are unchecked.)
- [ ] Reports show the correct counts by agent and by therapy after the walkthrough.
- [x] Guests are redirected to login on every new route; therapists cannot edit the therapy catalog. (Verified by automated tests, not manually.)
- [ ] Responsive: every new page shows no horizontal scroll at 320, 390, 768 and 1280px, and controls are at least 44px below `sm`. (Classes are asserted in tests; actual rendering is unchecked.)
- [ ] Renders correctly in current Chrome with no console errors. (Firefox and Safari are not required for the MVP.)
- [ ] Dashboard loads in under 1s on seeded data.
- [ ] Tone reads as witty and warm per `specs/mission.md`.

## Hygiene
- [x] `.env` is not committed.
- [x] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` checkboxes are updated. (Phase 1 checkboxes in the roadmap were already unticked before this work and are left as they were.)
- [x] No dead code (the 100% coverage gate enforces this).

## Known gaps
- Agents have no UI to set an email address; confirmation emails only reach agents seeded with one.
- The availability page is a list, not a calendar view.
