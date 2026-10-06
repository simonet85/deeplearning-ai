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

Administration pages (need the `users.manage` / `roles.manage` permission; administrators have both):

| Page | Path | What it does |
| --- | --- | --- |
| Users | `/admin/users` | Search accounts, change a user's role, and grant extra permissions on top of the role |
| Roles | `/admin/roles` | Add, rename and delete roles, and tick the permissions each role has |

Booking is atomic: a slot can only be booked once, and cancelling frees it. Removing a booked slot is blocked until the appointment is cancelled.

### Roles and permissions

Access is decided by permissions, handled with [spatie/laravel-permission](https://spatie.be/docs/laravel-permission). There are 14 permissions grouped by domain (dashboard, ailments, therapies, availability, appointments, the agent area, administration), listed with their labels in `app/Support/Access.php`. Three roles ship with the app and cannot be renamed or deleted: **admin** (every staff and administration permission), **therapist** (the staff pages, without managing therapies or other people's availability) and **agent** (the agent area). Administrators can add their own roles on the Roles page and give people a different role, or extra permissions, on the Users page.

A few rules keep everyone from being locked out: the admin role always keeps `users.manage` and `roles.manage`, nobody can change their own role or permissions, and a change to a role that would leave no one able to manage users and roles is undone. Giving someone a role with agent-area permissions creates their agent record. `sail artisan db:seed --class=RolesAndPermissionsSeeder` re-creates anything missing without touching changes you made.

### Profile photos

Every user (staff and agents) can add a profile photo on `/profile`: JPG, PNG or WebP, up to 10 MB. A photo that is already small (under 512 KB and no more than 800 px on a side) is kept as uploaded; a larger one is scaled down to fit 800 px and converted to WebP at quality 80 with [Intervention Image](https://image.intervention.io), so avatars stay light. Photos live on a private disk (`storage/app/private/profile-photos`) and are served by `/users/{id}/photo`, which checks who is asking: you can see your own photo, staff can see every photo, and an agent can never see another agent's. The photo shows in the navigation and on each agent's card on the staff dashboard, with the agent's initial as a fallback. After changing front-end classes, rebuild the assets with `sail npm run build`.

### Languages (English and French)

The whole app, its e-mails and its error pages are available in English and French. The language is picked automatically, in this order: the language saved on the signed-in user's account, then the `locale` cookie, then the browser's `Accept-Language` header (regions and weights are understood, so `fr-CA,fr;q=0.9` is French), then English. The **EN | FR** switcher in the navigation, on the login and welcome pages and on the error pages saves the choice in a cookie for a year and, when signed in, on the account, so it follows the user to another device. A new account keeps the language the visitor was reading when they signed up. E-mails (confirmations and reminders) are sent in the language of the agent's account, or in English for an agent without one, whoever booked the appointment. Dates and times follow the language (`Mon, Oct 5` / `lun. 5 oct.`, always on a 24-hour clock).

Translations live in `lang/fr.json` (the English text is the key, so the `__('English text')` calls need no English file) and in `lang/fr/*.php` for Laravel's validation, authentication and password messages and for the date styles. Data that people type or that is seeded (ailments, therapies, agent bios, custom role names) is not translated.

To add a language, add its code to `supported_locales` in `config/app.php`, copy `lang/fr.json` and `lang/fr/` to the new code and translate them. Three tests keep this honest: no string used by the app may lack a translation, `lang/fr.json` may not hold an unused entry, and a detector renders every page in a made-up language to prove that no English text was left outside `__()`.

### Error pages

403, 404, 419 (page expired), 429 (too many requests), 500 and 503 (maintenance) have their own pages in the clinic's colors and tone, in both languages, each with a way out chosen by who is looking (the dashboard for staff, My appointments for an agent, the profile for someone who can open neither, the home page for a visitor), a retry button where it makes sense, and the wait time when the response gives one. A 500 never shows technical detail. In the `local` and `testing` environments you can review them at `/_errors/{code}` (add `?retry=90` to see the wait time on 429 and 503); in production that address answers like any unknown URL.

### Confirmation emails

Booking sends a confirmation to the agent when the agent has an email address (seeded agents do, for example `pixel@agents.test`). In development every e-mail is caught by [Mailpit](https://mailpit.axllent.org), a mail server that runs in Docker with the rest of the stack and never sends anything for real. Open its inbox at http://localhost:8025 (SMTP is `mailpit:1025`, set in `.env` as `MAIL_MAILER=smtp`). To send real mail instead, point the `MAIL_*` variables in `.env` at your provider.


### Appointment reminders

A reminder e-mail goes to the agent about 24 hours before a booked appointment (once only, and not when the appointment was booked inside that window, since the confirmation covers it). Nothing to start by hand: `sail up -d` also runs two background services, defined in `compose.yaml`:

- `scheduler` runs `php artisan schedule:work`, which calls `reminders:send` every hour.
- `queue` runs `php artisan queue:listen`, which sends the queued reminder e-mails.

To try it right away, book an appointment that starts within 24 hours (or create one with a factory), run `sail artisan reminders:send`, and open http://localhost:8025: the reminder arrives within a few seconds and the appointment card shows "Reminder sent". Follow the services with `sail logs -f queue scheduler`.

Tests: `sail test` runs the PHPUnit suite (feature, Livewire component and unit tests) against a separate `testing` database. `sail composer test:coverage` enforces 100% line coverage of `app/`. Both must pass before merging.

## Look and feel

The interface follows the AgentClinic design system: warm paper (`surface`), scrubs-green (`scrub`) for actions, one balm accent, pill buttons and tags, Newsreader for headings and Instrument Sans for the interface (both self-hosted through `@fontsource`). The tokens live in `resources/css/tokens.css` as CSS variables; the night theme follows the system setting (`prefers-color-scheme`). `tailwind.config.js` maps the utility families the views already use (`gray`, `indigo`, `red`, `white`) onto those tokens, so a page follows the theme without per-class changes. `tests/Unit/DesignTokensTest.php` checks the contrast of every text pair in both themes. E-mails use the same colours in a light theme (`resources/views/vendor/mail`).
