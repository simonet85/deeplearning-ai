# Validation: Appointment Reminders

The `polish-and-scale` branch can merge when all of the following hold.

## Automated
- [ ] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [ ] `sail test` passes (PHPUnit). This is the merge gate.
- [ ] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [ ] Tests cover: the reminder scope (every exclusion and the inclusion case), the job (sent and marked, skipped when cancelled or without an e-mail), the mailable content, the command (queues only what is due, queues nothing when idle, idempotent on a second run), the hourly schedule entry, and the "Reminder sent" marker.

## Browser (automated, headed Chrome via Playwright, as for the MVP)
- [ ] Script books an appointment starting within 24 hours for an agent with an e-mail (created in the database, since the form only offers future slots at least as late as now), runs `sail artisan reminders:send` and the queue worker once, and checks that one reminder reaches the log mailer and the card shows "Reminder sent".
- [ ] Running the command again sends nothing more.
- [ ] A cancelled appointment gets no reminder.
- [ ] No console errors on the appointments page.

## Responsive
- [ ] The appointments page, with and without the "Reminder sent" marker, shows no horizontal scroll at 320, 390, 768 and 1280px, and controls are at least 44px below `sm`.

## Hygiene
- [ ] `.env` is not committed.
- [ ] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [ ] No dead code (the 100% coverage gate enforces this).
