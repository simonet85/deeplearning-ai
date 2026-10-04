# Validation: Foundation & Core Dashboard

The work can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against the Docker Postgres database.
- [x] `sail test` passes (PHPUnit): 43 tests, 132 assertions. This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] Tests cover: staff login/logout (Breeze auth tests), unauthenticated redirect, role-restricted access, dashboard renders seeded agents, seeders create expected records, layout landmarks and stylesheet link.
- [x] Livewire component tests cover the agent list (ordering, fields, empty state), and unit tests cover the `User` role helpers.

## Manual
- [ ] `sail up -d` plus the documented steps gives a working app from a clean checkout, with only Docker installed on the host. (Not yet run from a clean checkout; the README steps are untested on Windows.)
- [x] Log in as seeded admin and therapist; both reach the dashboard. (Verified in Chrome, desktop and mobile.)
- [x] Guest visiting the dashboard is redirected to login.
- [x] Dashboard lists the seeded agents and reads as witty and warm per `specs/mission.md`. (Five agents render; tone is a human judgment call.)
- [ ] Layout renders correctly in current Chrome, Firefox, and Safari, and at mobile width. (Chrome desktop and 390px mobile verified, no horizontal scroll or JS errors. Firefox and Safari not tested.)

## Hygiene
- [x] No leftover TypeScript scaffold files.
- [x] `.env` is not committed; `.env.example` is.
- [x] README documents setup.

## Branding
- [x] The default Laravel logo is replaced with an AgentClinic clinic-cross mark (navigation and login page).
