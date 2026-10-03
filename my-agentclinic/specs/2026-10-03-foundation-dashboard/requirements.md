# Requirements: Foundation & Core Dashboard (Roadmap Phase 1)

## Scope
Deliver the full Phase 1 from `specs/roadmap.md`:
- Laravel + Livewire project scaffolding
- Staff authentication with roles
- Dashboard landing page with welcome and navigation
- Core models: `Agent`, `User` (staff)
- Seed data: sample agents and staff

Out of scope: ailments, therapies, appointments, notifications, reporting (Phases 2+).

## Decisions
- **Auth**: Laravel Breeze, Livewire stack.
- **Roles**: only staff log in. `users` has a `role` (`admin`, `therapist`). Agents are records in an `agents` table that staff manage; agents have no login in this phase.
- **Database**: Postgres via Docker from day one, to match production.
- **Testing**: PHPUnit feature tests.
- **Tone**: satirical, warm, per `specs/mission.md`.

## Context
- Stack per `specs/tech-stack.md`: Laravel (PHP 8.3+), Blade + Livewire, Eloquent, migrations.
- Stakeholders: Mary (reliable, dashboard), Steve (attractive, modern browsers).
- The existing TypeScript scaffold in this directory (`package.json`, `tsconfig.json`, `src/index.ts`) predates the Laravel decision and is to be removed as part of scaffolding.

## Data
- `users`: name, email, password, role
- `agents`: name, agent_type, bio

## Open Questions
None.
