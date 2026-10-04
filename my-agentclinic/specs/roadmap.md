# AgentClinic Roadmap

## Phasing Strategy: UI-First with Emerging Schema
Since Livewire is component-driven, we build user-facing features first. Database schema emerges organically as we implement each feature. This keeps the team aligned on what users actually need. Every phase's UI is responsive (mobile-first, per `tech-stack.md`), so each deliverable below must work from phone to desktop.

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

## Phase 2: Clinic Operations: Ailments, Therapies, Booking & Polish (Weeks 3–8, then ongoing)
**Goal**: The full clinic loop works end to end: agents express what's wrong, staff prescribe therapies, appointments get booked, and the product is polished and ready to scale.

### Deliverables

#### Ailments & Therapies
- [ ] Livewire component: Agent ailments list/form
- [ ] Livewire component: Therapies catalog
- [ ] Staff dashboard: manage therapies
- [ ] Agent view: browse and rate therapies
- [ ] Relationships: `Ailment`, `Therapy`, `AilmentTherapy` (many-to-many)

#### Appointment Booking
- [ ] Agent: view availability, book appointment
- [ ] Staff: manage availability calendar
- [ ] Confirmation emails/notifications
- [ ] Appointment dashboard: view booked sessions
- [ ] Simple reporting: appointments by agent/therapy

#### Polish & Scale
- [ ] Appointment reminder notifications
- [ ] Agent wellness scores/metrics
- [ ] Advanced reporting (trends, peak times)
- [ ] Performance optimization
- [ ] Audit logging

### Database
- `ailments` table (name, description, severity_scale)
- `therapies` table (name, description, duration, type)
- `ailment_therapy` pivot table (which therapies treat which ailments)
- `appointments` table (agent_id, therapist_id, therapy_id, datetime, status)
- `availability` table (therapist_id, date, time_slot)

### Why Second
Builds on the Phase 1 foundation and delivers everything stakeholders asked for in one phase: the domain concepts (ailments, therapies), the complete booking journey, and the polish that follows real usage. Ailments and therapies come first within the phase because booking depends on them.

---

## Checkpoints
- **End of Phase 1**: Stakeholders see working auth, dashboard, brand presence
- **End of Phase 2**: Core domain (ailments/therapies) live, full appointment booking loop functional, and the polish and scale items delivered

## Success Criteria
- Dashboard loads fast (<1s)
- Booking completes in <5 clicks
- All browsers (Safari, Chrome, Firefox) render correctly
- Responsive from 320px to desktop: no horizontal scroll, 44px touch targets on small screens
- Every phase ships with passing tests (PHPUnit plus Livewire component tests, per `tech-stack.md`); `sail test` passes before any merge
