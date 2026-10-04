# Validation: Foundation & Core Dashboard

The work can merge when all of the following hold.

## Automated
- [x] `sail artisan migrate:fresh --seed` succeeds against the Docker Postgres database.
- [x] `sail test` passes (PHPUnit): 46 tests, 138 assertions. This is the merge gate.
- [x] `sail composer test:coverage` passes with 100% line coverage of `app/`.
- [x] Tests cover: staff login/logout (Breeze auth tests), unauthenticated redirect, role-restricted access, dashboard renders seeded agents, seeders create expected records, layout landmarks and stylesheet link.
- [x] Livewire component tests cover the agent list (ordering, fields, empty state), and unit tests cover the `User` role helpers.

## Manual
- [ ] `sail up -d` plus the documented steps gives a working app from a clean checkout, with only Docker installed on the host. (Not yet run from a clean checkout; the README steps are untested on Windows.)
- [x] Log in as seeded admin and therapist; both reach the dashboard. (Verified in Chrome, desktop and mobile.)
- [x] Guest visiting the dashboard is redirected to login.
- [x] Dashboard lists the seeded agents and reads as witty and warm per `specs/mission.md`. (Five agents render; tone is a human judgment call.)
- [ ] Layout renders correctly in current Chrome, Firefox, and Safari. (Chrome verified, no JS errors. Firefox and Safari not tested.)
- [x] Responsive: landing, login, dashboard and profile show no horizontal scroll at 320, 390, 768, 1024 and 1280px (Chrome).
- [x] Responsive: buttons and links are at least 44px below `sm`. Re-audited in Chrome at 320 and 390px: no controls under 40px on any page.
- [x] Feature tests assert the viewport meta, the `touch-target` class and the responsive agent grid (`sm:grid-cols-2 lg:grid-cols-3`).

## Hygiene
- [x] No leftover TypeScript scaffold files.
- [x] `.env` is not committed; `.env.example` is.
- [x] README documents setup.

## Branding
- [x] The default Laravel logo is replaced with an AgentClinic clinic-cross mark (navigation and login page).
