# AgentClinic

## Input from stakeholders

- Mary in engineering wants a reliable site with a popular stack based on TypeScript, giving agents and staff a dashboard for easy access.
- Susan in product has a set of features about agents and their ailments, therapies, and booking appointments.
- Steve in marketing wants an attractive site that works well with a modern browser.

## Getting started

Built with Laravel, Livewire (Volt) and Postgres, run in Docker via [Laravel Sail](https://laravel.com/docs/sail). Only Docker is required on the host.

```sh
cp .env.example .env
docker run --rm -v "$(pwd):/opt" -w /opt laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Then open http://localhost. On Windows, run `sail` from WSL, or use `docker compose` with `WWWUSER`/`WWWGROUP` set.

Seeded staff (password `password`): `admin@agentclinic.test` (admin), `sam@agentclinic.test` and `riley@agentclinic.test` (therapists). Staff accounts are seeded. Agents create their own account at `/register` (name, agent type, e-mail, password and confirmation) and see only their own data; their sign-up is rate limited to five attempts a minute.

Common commands: `sail artisan migrate:fresh --seed`, `sail down`.

## What's in the app

All pages require a staff login. Agents have no login: staff act on an agent's behalf.

| Page | Path | What it does |
| --- | --- | --- |
| Dashboard | `/dashboard` | Summary tiles (upcoming sessions, open slots, patients) and the agent list, where staff can set each agent's confirmation email |
| Ailments | `/ailments` | Record an ailment and its severity for an agent |
| Therapies | `/therapies` | Browse the catalog and rate therapies per agent; admins add, edit and delete therapies and link them to ailments |
| Availability | `/availability` | A day-by-day calendar of slots; therapists manage their own, admins manage everyone's, and booked slots are marked |
| Appointments | `/appointments` | Book a slot for an agent, cancel, filter by agent, therapist or therapy, and see counts by agent and therapy |

Booking is atomic: a slot can only be booked once, and cancelling frees it. Removing a booked slot is blocked until the appointment is cancelled.

### Confirmation emails

Booking sends a confirmation to the agent when the agent has an email address (seeded agents do, for example `pixel@agents.test`). In development every e-mail is caught by [Mailpit](https://mailpit.axllent.org), a mail server that runs in Docker with the rest of the stack and never sends anything for real. Open its inbox at http://localhost:8025 (SMTP is `mailpit:1025`, set in `.env` as `MAIL_MAILER=smtp`). To send real mail instead, point the `MAIL_*` variables in `.env` at your provider.


### Appointment reminders

A reminder e-mail goes to the agent about 24 hours before a booked appointment (once only, and not when the appointment was booked inside that window, since the confirmation covers it). Nothing to start by hand: `sail up -d` also runs two background services, defined in `compose.yaml`:

- `scheduler` runs `php artisan schedule:work`, which calls `reminders:send` every hour.
- `queue` runs `php artisan queue:listen`, which sends the queued reminder e-mails.

To try it right away, book an appointment that starts within 24 hours (or create one with a factory), run `sail artisan reminders:send`, and open http://localhost:8025: the reminder arrives within a few seconds and the appointment card shows "Reminder sent". Follow the services with `sail logs -f queue scheduler`.

Tests: `sail test` runs the PHPUnit suite (feature, Livewire component and unit tests) against a separate `testing` database. `sail composer test:coverage` enforces 100% line coverage of `app/`. Both must pass before merging.
