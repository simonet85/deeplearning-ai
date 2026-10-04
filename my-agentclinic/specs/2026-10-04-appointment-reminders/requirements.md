# Requirements: Appointment Reminders (Roadmap Phase 2, "Polish & Scale", first item)

## Scope
Send each agent one reminder e-mail 24 hours before a booked appointment, using the queue and the scheduler.

In scope:
- A scheduled command that finds booked appointments starting within the next 24 hours and queues a reminder for each.
- A queued job that sends the reminder e-mail.
- A "Reminder sent" marker on the appointment card so staff can see it happened.
- Documentation of how to run the queue worker and the scheduler under Sail.

Out of scope (still open in the roadmap, not part of this step): agent wellness scores and metrics, advanced reporting (trends, peak times), performance optimization, audit logging, a 1-hour second reminder, SMS or push notifications.

## Decisions
- **Timing**: one reminder, 24 hours before the start. The scheduler runs the command every hour, so a reminder goes out between 23 and 24 hours before the appointment.
- **Queue and scheduler**: the command queues a job per appointment (`QUEUE_CONNECTION=database`, already the default in `.env.example`). A worker (`sail artisan queue:work`) sends the e-mails and the scheduler (`sail artisan schedule:work`) runs the command hourly. Tests use the `sync` queue and `Mail::fake()`.
- **Who gets one**: appointments with status `booked`, a start time in the future within 24 hours, no reminder sent yet, and an agent with an e-mail address.
- **No reminder right after booking**: if an appointment was booked less than 24 hours before it starts, the confirmation e-mail already covers it, so no reminder is sent.
- **Idempotent**: `appointments.reminder_sent_at` is set when the job sends the e-mail, and the command skips appointments where it is set. Running the command twice never sends two reminders.
- **Cancelled in the meantime**: the job re-checks the status when it runs and does nothing if the appointment is no longer `booked`.
- **Tone**: witty and warm, per `specs/mission.md`, matching the confirmation e-mail.
- **Wellness score (decided for a later step)**: when scores are built, use a simple 0-100 score based on the severity of open ailments, adjusted by attended sessions and given ratings. The formula must be documented and unit tested. This is recorded here so it is not re-decided; it is not built in this step.
- **Testing**: PHPUnit per `specs/tech-stack.md`; 100% line coverage of `app/`; `RefreshDatabase` and factories.
- **Responsive**: the only UI change is the "Reminder sent" marker on the appointment card, built mobile-first like the rest of the page.

## Context
- Builds on the MVP: the `AppointmentBooked` mailable, `Appointment` model and `agents.email`, and the appointments page (`specs/2026-10-04-mvp/`).
- The `jobs` table already exists (default Laravel migration); `routes/console.php` holds only the sample command, and there is no `app/Console` directory yet.
- Sail runs the app in one container; the queue worker and scheduler are extra processes the README must document.

## Data
- `appointments.reminder_sent_at`: nullable timestamp.

## Open Questions
- Should staff be able to resend a reminder by hand? (Default: no.)
