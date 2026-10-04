# Validation: Appointment Reminders

The `polish-and-scale` branch can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against Docker Postgres.
- [x] `sail test` passes (PHPUnit): 156 tests, 510 assertions. This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] Tests cover: the reminder scope (every exclusion and the inclusion case), the job (sent and marked, skipped when cancelled, already reminded, or without an e-mail), the mailable content, the command (queues only what is due, queues nothing when idle, idempotent on a second run), the hourly schedule entry, and the "Reminder sent" marker.

## Browser (automated, headed Chrome via Playwright, as for the MVP)
Run on 2026-10-04: 20 checks, all passing.
- [x] With one due appointment (Pixel) and one cancelled appointment (Scout) in the database, `reminders:send` queues exactly one reminder, the queue worker sends it, and one reminder reaches the log mailer.
- [x] The card shows "Reminder sent" afterwards; before the run it did not.
- [x] Running the command and the worker again sends nothing more.
- [x] The cancelled appointment gets no reminder and no marker.
- [x] `schedule:list` shows `reminders:send`.
- [x] No console errors on the appointments page.

## Responsive
- [x] The appointments page with the "Reminder sent" marker shows no horizontal scroll at 320, 390, 768 and 1280px, and controls are at least 44px at 320 and 390px.

## Hygiene
- [x] `.env` is not committed.
- [x] `README.md`, `CHANGELOG.md` and `specs/roadmap.md` are updated.
- [x] No dead code (the 100% coverage gate enforces this).

## Known gaps
- The scheduler and the queue worker are separate processes that must be started by hand (`sail artisan schedule:work` and `sail artisan queue:work`); nothing starts them automatically in Sail.
- The appointment was created in the database for the browser run, because the booking form only offers slots that have not started and the 24-hour window made a real booking impractical to wait for.
