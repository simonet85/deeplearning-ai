# Plan: Appointment Reminders

Vertical slices, each with its tests. Run `sail test` after every slice.

## 1. Data
1. Migration adding nullable `reminder_sent_at` to `appointments`.
2. Cast it to `datetime` on `Appointment`; add it to `$fillable`.
3. Add a `needsReminder` query scope: status `booked`, start within the next 24 hours and in the future, `reminder_sent_at` null, booked at least 24 hours before the start.
4. Tests for the scope: each exclusion (cancelled, already reminded, too far ahead, in the past, booked inside the window) and the inclusion case.

## 2. Reminder job and e-mail
1. `AppointmentReminder` mailable with a witty, warm view (`resources/views/mail/appointment-reminder.blade.php`).
2. `SendAppointmentReminder` queued job: re-check status is still `booked`, skip when the agent has no e-mail, send the mailable, set `reminder_sent_at`.
3. Tests with `Mail::fake()`: sent and marked; skipped when cancelled meanwhile; skipped without an e-mail; mail content (subject, agent, therapy, therapist, time).

## 3. Scheduled command
1. `reminders:send` Artisan command: queue one job per appointment returned by the scope and report how many it queued.
2. Register it in `routes/console.php` to run hourly.
3. Tests with `Queue::fake()`: queues only the appointments that need a reminder, queues nothing when none do, and the schedule contains the hourly entry.
4. An end-to-end test on the sync queue: command runs, mail is sent, a second run sends nothing.

## 4. Appointment card marker
1. Show "Reminder sent" (with the time) on the appointment card when `reminder_sent_at` is set.
2. Tests: shown after a reminder, not shown before.

## 5. Docs and wrap-up
1. README: how to run `sail artisan queue:work` and `sail artisan schedule:work`, and how to try it by hand with `sail artisan reminders:send`.
2. Update `specs/roadmap.md` (tick "Appointment reminder notifications") and `CHANGELOG.md`.
3. Run `sail test` and `sail composer test:coverage` (100%).
4. Run the browser walkthrough in Chrome (see `validation.md`) and the responsive check.
5. Complete `validation.md` and merge `polish-and-scale` into `main`.
