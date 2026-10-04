# AgentClinic Tech Stack

## Framework: Laravel + Livewire

### Why Laravel + Livewire?
- **Reliability**: Battle-tested, mature framework with strong ecosystem (Mary's requirement)
- **Full-Stack PHP**: Single language across backend and frontend logic
- **Livewire Magic**: Real-time, interactive components without JavaScript complexity—perfect for dashboards and appointment booking
- **Developer Experience**: Clean syntax, built-in ORM (Eloquent), migrations, and testing tools
- **Browser Compatibility**: Modern Laravel apps work seamlessly across all current browsers (Steve's requirement)

### Architecture
- **Backend**: Laravel (PHP 8.3+)
  - Eloquent ORM for data models (Agents, Ailments, Therapies, Appointments)
  - API routes for future integrations
  - Authentication & authorization
  - Database migrations for schema evolution

- **Frontend**: Blade templates + Livewire components
  - Server-rendered with client-side interactivity
  - Dashboard for agents and staff
  - Appointment booking UI
  - Real-time updates without page reloads

- **Database**: MySQL/PostgreSQL
  - Structured schema for agents, ailments, therapies, appointments
  - Audit trail for compliance

### Testing
- **Framework**: PHPUnit, the Laravel default, with Laravel's HTTP test helpers and Livewire's component testing (`Livewire::test(...)`) for interactive components.
- **Run**: `sail test` (or `sail composer test`). Tests run inside the Sail container against a separate `testing` Postgres database, so development data is never touched.
- **Layout**: feature tests in `tests/Feature` (HTTP, auth, roles, Livewire components, seeders); unit tests in `tests/Unit` for isolated logic.
- **Conventions**: use `RefreshDatabase` and model factories; no mocking of the database. Every feature spec's `validation.md` must be backed by passing tests plus a short manual browser check.
- **Coverage**: 100% line coverage of `app/`, enforced by `sail composer test:coverage` (Xdebug coverage mode, `--min=100`). Dead code is deleted rather than tested around. Coverage measures PHP classes only; Blade and Volt views are exercised by HTTP and Livewire tests.
- **Merge gate**: `sail test` and `sail composer test:coverage` must pass before merging.
- **Not used**: Vitest and other JavaScript test runners. The frontend is server-rendered Blade and Livewire with almost no custom JS. Revisit if substantial client-side JS is added.

### Deployment
- Single Laravel application deployed on standard PHP hosting or containerized (Docker)
- CDN for static assets
- Minimal DevOps complexity

### Rationale vs. Original TypeScript Plan
TypeScript is popular, but Laravel + Livewire delivers the same goals (reliable, popular, dashboard-friendly) with less frontend/backend coordination overhead and stronger built-in features for real-time features.
