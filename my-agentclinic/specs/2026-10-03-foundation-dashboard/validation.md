# Validation: Foundation & Core Dashboard

The work can merge when all of the following hold.

## Automated
- [ ] `php artisan migrate:fresh --seed` succeeds against the Docker Postgres database.
- [ ] `php artisan test` passes (PHPUnit).
- [ ] Tests cover: staff login/logout, unauthenticated redirect, role-restricted access, dashboard renders seeded agents, seeders create expected records.

## Manual
- [ ] `docker compose up` plus the documented steps gives a working app from a clean checkout.
- [ ] Log in as seeded admin and therapist; both reach the dashboard.
- [ ] Guest visiting the dashboard is redirected to login.
- [ ] Dashboard lists the seeded agents and reads as witty and warm per `specs/mission.md`.
- [ ] Layout renders correctly in current Chrome, Firefox, and Safari, and at mobile width.

## Hygiene
- [ ] No leftover TypeScript scaffold files.
- [ ] `.env` is not committed; `.env.example` is.
- [ ] README documents setup.
