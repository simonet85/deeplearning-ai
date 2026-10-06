# Changelog

All notable changes to AgentClinic, grouped by date (newest first).
Maintained with the `changelog` skill; run it before merging.

<!-- changelog-last-commit: 4c28b06 -->

## 2026-10-04

- Added the Ailments page: staff record an ailment and its severity for an agent, with five seeded satirical ailments.
- Added the Therapies page: a catalog showing each therapy's type, duration and treated ailments, with admin-only add, edit and delete.
- Added therapy ratings: staff rate a therapy on an agent's behalf (1-5, one per agent and therapy) and the catalog shows the average.
- Added the Availability page: therapists manage their own open slots, admins manage everyone's, and duplicate or past slots are rejected.
- Added appointment booking: staff book an open slot for an agent, double booking is prevented, and cancelling frees the slot; a booked slot can't be removed.
- Added appointment filters (agent, therapist, therapy) with upcoming and past lists, a report of appointments by agent and by therapy, and summary tiles on the dashboard.
- Added a confirmation email sent on booking when the agent has an address; agents gained an optional email and the `log` mailer is used in development.
- Added English and French across the app: the language is detected from the browser, can be switched with an EN | FR control and is remembered on the account, and every page, e-mail, validation message, date and time follows it.
- Added designed error pages for 403, 404, 419, 429, 500 and 503, in both languages and with a way out for each kind of visitor.
- Replaced the fixed roles with permissions (spatie/laravel-permission): 14 permissions by domain, the admin, therapist and agent roles migrated from the old column, and new Users and Roles pages where administrators change a user's role, grant extra permissions, and add, rename, delete and edit roles, with safeguards against locking everyone out.
- Restyled the whole app with the AgentClinic design system: warm paper and scrubs-green, Newsreader and Instrument Sans fonts (self-hosted), pill buttons and tags, a new logo, and a night theme that follows the system setting; e-mails and error pages follow too.
- Added profile photos for every user: upload from the profile page, with large images resized to 800 px and converted to WebP by Intervention Image, kept on a private disk and shown in the navigation and on the staff dashboard, visible only to their owner and to staff.
- Added agent accounts: agents register themselves at `/register` and get their own area to record their ailments, book and cancel their own appointments, and edit their profile, while staff pages are closed to them and each agent sees only their own data.
- Added appointment reminders: an hourly scheduled command queues one reminder e-mail per booked appointment starting within 24 hours, the card shows "Reminder sent", and the README explains how to run the queue worker and the scheduler.
- Added a `queue` worker, a `scheduler` and a Mailpit mail catcher to the Docker stack, so reminders run on their own and every e-mail can be read at http://localhost:8025 instead of the log.
- Added an "Edit email" control on each agent card so staff can set where booking confirmations go, and turned the availability list into a day-by-day calendar that shows booked slots.
- Fixed issues found in a browser pass: the report now updates live after booking or cancelling, the therapy form no longer throws a JavaScript error, the mobile menu button meets the 44px touch target, and same-time open slots are ordered consistently.
- Added responsive tests for the new pages and documented them in the README.
- Wrote the MVP plan, requirements and validation specs and ticked the delivered Phase 2 roadmap items.
- Added a main layout component (`<x-layout>`) built from header, main and footer subcomponents, with its styles in `resources/css/layout.css`.
- Adopted PHPUnit as the testing approach: Livewire component tests, unit tests, a login lockout test and an email verification test.
- Reached 100% line coverage of `app/`, enforced by the new `composer test:coverage` script.
- Made the UI responsive and mobile-first: 44px touch targets on small screens, with the rules written into the product and Phase 1 specs.
- Combined roadmap phases 2–4 into a single Phase 2.

## 2026-10-03

- Implemented Phase 1: Laravel on Docker with Laravel Sail and Postgres, Livewire, and staff login with admin and therapist roles (registration disabled).
- Added the `Agent` model, seed data for staff and agents, and a dashboard listing agents, with a branded landing page and logo.
- Wrote the Phase 1 plan, requirements and validation specs.
- Removed the original TypeScript project scaffold in favor of Laravel.
- Added the project constitution: mission, tech stack and roadmap.
- Initialized the project with a TypeScript scaffold.
