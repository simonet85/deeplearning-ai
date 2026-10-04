# Requirements: MVP Clinic Loop (Roadmap Phase 2, MVP cut)

## Scope
The smallest end-to-end clinic loop from Phase 2 of `specs/roadmap.md`. Phase 1 (foundation, auth, dashboard, `Agent`/`User`) is already in place.

In scope:
- **Ailments & Therapies**
  - Ailments list/form (recorded per agent)
  - Therapies catalog
  - Staff manage therapies (create, edit, delete)
  - Ailment-to-therapy many-to-many (`Ailment`, `Therapy`, `ailment_therapy`)
  - Browse and rate therapies
- **Appointment booking**
  - Staff manage availability
  - Book an appointment for an agent
  - Appointment dashboard of booked sessions
- **Simple reporting**: appointments by agent and by therapy
- **Confirmation emails/notifications** on booking

Out of scope (post-MVP "Polish & Scale"): appointment reminders, wellness scores/metrics, advanced reporting (trends, peak times), performance optimization, audit logging, agent logins.

## Decisions
- **Agents have no login.** Staff act on behalf of agents: staff choose the agent when recording an ailment, rating a therapy, or booking. This keeps the Phase 1 auth decision (only staff log in) and avoids an auth rework.
- **Ratings** are recorded by staff for an agent (one rating per agent per therapy, 1-5), and the catalog shows the average.
- **Availability** belongs to a therapist (`users` with role `therapist`) as dated time slots. Booking consumes a slot; a slot can be booked once (no double booking).
- **Appointment status**: `booked`, `cancelled`, `completed`. MVP creates `booked` and allows cancel; cancelling frees the slot.
- **Authorization**: all new pages are behind auth. Admins manage everything; therapists manage their own availability and see all appointments.
- **Notifications**: Laravel mailables sent on booking to the agent's contact address when present, otherwise skipped. Dev uses the `log` mailer; tests use `Mail::fake()`. No queue worker is required (send synchronously).
- **Phasing**: vertical slices (migration, model, factory, Livewire UI, tests per slice) so each slice is demoable.
- **Testing**: PHPUnit per `specs/tech-stack.md`; Livewire (Volt) component tests; `RefreshDatabase` and factories; 100% line coverage of `app/`.
- **Responsive**: every new page is mobile-first, 320px to desktop, 44px touch targets via `.touch-target`, built in `<x-layout>`.
- **Tone**: satirical, warm, per `specs/mission.md` (e.g. ailments like "Token Fatigue", therapies like "Context Window Spa").

## Context
- Stack: Laravel + Livewire (Volt) + Blade, Postgres via Sail (`specs/tech-stack.md`).
- Existing models: `Agent` (name, agent_type, bio), `User` (name, email, password, role). Existing UI: `agent-list` Volt component, dashboard, `<x-layout>`.
- Foundation spec for conventions: `specs/2026-10-03-foundation-dashboard/`.
- Roadmap success criteria apply: dashboard loads in under 1s; booking completes in under 5 clicks.

## Data
- `ailments`: name, description, severity_scale
- `agent_ailment` (an agent's recorded ailment): agent_id, ailment_id, severity, notes
- `therapies`: name, description, duration (minutes), type
- `ailment_therapy`: ailment_id, therapy_id
- `therapy_ratings`: agent_id, therapy_id, rating (unique per agent and therapy)
- `availability`: therapist_id, date, time_slot
- `appointments`: agent_id, therapist_id, therapy_id, availability_id, datetime, status
- `agents` gains a nullable `email` for confirmation emails.

## Open Questions
- Should `ailment_therapy` carry an effectiveness note? (Default: no, plain pivot.)
