# AgentClinic Roadmap

## Phasing Strategy: UI-First with Emerging Schema
Since Livewire is component-driven, we build user-facing features first. Database schema emerges organically as we implement each feature. This keeps the team aligned on what users actually need.

---

## Phase 1: Foundation & Core Dashboard (Weeks 1–2)
**Goal**: Agents and staff see a working, delightful interface.

### Deliverables
- [ ] Laravel project scaffolding (auth, migrations, Livewire setup)
- [ ] Agent & Staff roles and basic auth
- [ ] Dashboard landing page with welcome + navigation
- [ ] Core data models: `Agent`, `User` (staff/therapist)
- [ ] Seed data: sample agents and staff members

### Database
- `agents` table (name, agent_type, bio)
- `users` table (name, role, email)
- Authentication/sessions

### Why First
Shows stakeholders a working product immediately. Establishes UI patterns and brand tone. Enables booking features to follow.

---

## Phase 2: Ailments & Therapies (Weeks 3–4)
**Goal**: Agents can express what's wrong; staff can prescribe solutions.

### Deliverables
- [ ] Livewire component: Agent ailments list/form
- [ ] Livewire component: Therapies catalog
- [ ] Staff dashboard: manage therapies
- [ ] Agent view: browse and rate therapies
- [ ] Relationships: `Ailment`, `Therapy`, `AilmentTherapy` (many-to-many)

### Database
- `ailments` table (name, description, severity_scale)
- `therapies` table (name, description, duration, type)
- `ailment_therapy` pivot table (which therapies treat which ailments)

### Why Second
Builds on Phase 1 foundation. Establishes domain concepts. Preps for appointment booking.

---

## Phase 3: Appointment Booking MVP (Weeks 5–6)
**Goal**: End-to-end booking flow works.

### Deliverables
- [ ] Agent: view availability, book appointment
- [ ] Staff: manage availability calendar
- [ ] Confirmation emails/notifications
- [ ] Appointment dashboard: view booked sessions
- [ ] Simple reporting: appointments by agent/therapy

### Database
- `appointments` table (agent_id, therapist_id, therapy_id, datetime, status)
- `availability` table (therapist_id, date, time_slot)

### Why Third
Completes the core user journey. Shows ROI to stakeholders. Provides real usage data for Phase 4+.

---

## Phase 4+: Polish & Scale (Ongoing)
- [ ] Appointment reminder notifications
- [ ] Agent wellness scores/metrics
- [ ] Advanced reporting (trends, peak times)
- [ ] Mobile-responsive refinement
- [ ] Performance optimization
- [ ] Audit logging

---

## Checkpoints
- **End of Phase 1**: Stakeholders see working auth, dashboard, brand presence
- **End of Phase 2**: Core domain (ailments/therapies) live and usable
- **End of Phase 3**: Full appointment booking loop functional

## Success Criteria
- Dashboard loads fast (&lt;1s)
- Booking completes in &lt;5 clicks
- All browsers (Safari, Chrome, Firefox) render correctly
- Zero test coverage on Phase 1–3 critical paths (added later)
