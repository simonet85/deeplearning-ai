# Validation: MVP Clinic Loop

The `mvp` branch can merge when all of the following hold.

## Automated
- [ ] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [ ] `sail test` passes (PHPUnit). This is the merge gate.
- [ ] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [ ] Tests cover: ailments (list, create, validation), therapies (catalog, staff CRUD, role restrictions, pivot), ratings (create, update, average), availability (CRUD, ownership, duplicates), booking (happy path, double booking rejected, cancel frees slot), dashboard and report totals, and confirmation email (sent with address, skipped without, via `Mail::fake()`).
- [ ] Feature tests assert the viewport meta, `touch-target` class and responsive grid classes on new pages.

## Manual
- [ ] End-to-end walkthrough: log in as a therapist and add availability; log in as admin, record an ailment for an agent, book an appointment for that agent in under 5 clicks; the appointment shows on the dashboard; the confirmation email appears in the log mailer output; attempting to book the same slot again is rejected; cancelling frees the slot.
- [ ] Reports show the correct counts by agent and by therapy after the walkthrough.
- [ ] Guests are redirected to login on every new route; therapists cannot edit the therapy catalog.
- [ ] Responsive: every new page shows no horizontal scroll at 320, 390, 768 and 1280px, and controls are at least 44px below `sm`.
- [ ] Renders correctly in current Chrome with no console errors. (Firefox and Safari are not required for the MVP.)
- [ ] Dashboard loads in under 1s on seeded data.
- [ ] Tone reads as witty and warm per `specs/mission.md`.

## Hygiene
- [ ] `.env` is not committed.
- [ ] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` checkboxes are updated.
- [ ] No dead code (coverage gate enforces this).
