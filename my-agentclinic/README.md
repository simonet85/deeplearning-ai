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

All pages require a login. There are three roles: **admin** and **therapist** (staff, seeded) and **agent** (self-registered). Staff can act on an agent's behalf; agents see only their own data.

Staff pages (administrators and therapists only; agents get a 403):

| Page | Path | What it does |
| --- | --- | --- |
| Dashboard | `/dashboard` | Summary tiles (upcoming sessions, open slots, patients) and the agent list, where staff can set each agent's confirmation email |
| Ailments | `/ailments` | Record an ailment and its severity for an agent |
| Therapies | `/therapies` | Browse the catalog and rate therapies per agent; admins add, edit and delete therapies and link them to ailments |
| Availability | `/availability` | A day-by-day calendar of slots; therapists manage their own, admins manage everyone's, and booked slots are marked |
| Appointments | `/appointments` | Book a slot for an agent, cancel, filter by agent, therapist or therapy, and see counts by agent and therapy |

Agent pages (agents only; staff get a 403). Agents sign up themselves at `/register` and are sent to **My appointments**:

| Page | Path | What it does |
| --- | --- | --- |
| My appointments | `/me/appointments` | Book an open slot for yourself, see your upcoming and past sessions, and cancel your own |
| My ailments | `/me/ailments` | Record your own ailments and see your history |
| Profile | `/profile` | Add or change your profile photo; change your name, e-mail, agent type, bio and password; or delete your account (your agent record and history stay with the clinic) |

Booking is atomic: a slot can only be booked once, and cancelling frees it. Removing a booked slot is blocked until the appointment is cancelled.

### Profile photos

Every user (staff and agents) can add a profile photo on `/profile`: JPG, PNG or WebP, up to 10 MB. A photo that is already small (under 512 KB and no more than 800 px on a side) is kept as uploaded; a larger one is scaled down to fit 800 px and converted to WebP at quality 80 with [Intervention Image](https://image.intervention.io), so avatars stay light. Photos live on a private disk (`storage/app/private/profile-photos`) and are served by `/users/{id}/photo`, which checks who is asking: you can see your own photo, staff can see every photo, and an agent can never see another agent's. The photo shows in the navigation and on each agent's card on the staff dashboard, with the agent's initial as a fallback. After changing front-end classes, rebuild the assets with `sail npm run build`.

### Confirmation emails

Booking sends a confirmation to the agent when the agent has an email address (seeded agents do, for example `pixel@agents.test`). In development every e-mail is caught by [Mailpit](https://mailpit.axllent.org), a mail server that runs in Docker with the rest of the stack and never sends anything for real. Open its inbox at http://localhost:8025 (SMTP is `mailpit:1025`, set in `.env` as `MAIL_MAILER=smtp`). To send real mail instead, point the `MAIL_*` variables in `.env` at your provider.


### Appointment reminders

A reminder e-mail goes to the agent about 24 hours before a booked appointment (once only, and not when the appointment was booked inside that window, since the confirmation covers it). Nothing to start by hand: `sail up -d` also runs two background services, defined in `compose.yaml`:

- `scheduler` runs `php artisan schedule:work`, which calls `reminders:send` every hour.
- `queue` runs `php artisan queue:listen`, which sends the queued reminder e-mails.

To try it right away, book an appointment that starts within 24 hours (or create one with a factory), run `sail artisan reminders:send`, and open http://localhost:8025: the reminder arrives within a few seconds and the appointment card shows "Reminder sent". Follow the services with `sail logs -f queue scheduler`.

Tests: `sail test` runs the PHPUnit suite (feature, Livewire component and unit tests) against a separate `testing` database. `sail composer test:coverage` enforces 100% line coverage of `app/`. Both must pass before merging.
