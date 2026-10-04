# Plan: MVP Clinic Loop

Vertical slices: each slice ships migration, model, factory, seeder data, Livewire UI, and tests. Run `sail test` after every slice.

## 1. Ailments
1. Migrations, models, factories for `Ailment` and the agent-ailment record (`agent_ailment`).
2. Seed satirical ailments (token fatigue, prompt ambiguity, goal misalignment).
3. Volt component: ailments list and form, recorded for a chosen agent; link from the agent list.
4. Route behind auth and add navigation entry.
5. Tests: Volt component (list, create, validation, empty state), model relationships.

## 2. Therapies
1. Migrations, models, factories for `Therapy` and the `ailment_therapy` pivot; relationships on both models.
2. Seed therapies and attach them to ailments.
3. Volt component: therapies catalog (responsive grid, shows treated ailments and average rating).
4. Staff management: create, edit, delete therapies (admin only; therapists read-only).
5. Tests: catalog, CRUD, role restrictions, pivot sync.

## 3. Ratings
1. Migration, model, factory for `therapy_ratings` (unique agent and therapy).
2. Rate form on a therapy: staff pick the agent and a 1-5 rating; re-rating updates.
3. Average rating on the catalog.
4. Tests: create, update, validation, average calculation.

## 4. Availability
1. Migration, model, factory for `availability` (therapist, date, time slot).
2. Volt component: staff manage availability (add and remove slots); therapists see only their own, admins see all.
3. Prevent duplicate slots and past dates.
4. Tests: CRUD, ownership rules, validation.

## 5. Booking
1. Migration, model, factory for `appointments`; add nullable `email` to `agents`.
2. Volt component: choose agent, therapy, and an open slot; book in under 5 clicks.
3. Booking consumes the slot atomically (transaction and unique constraint) so double booking is impossible.
4. Cancel an appointment, which frees the slot.
5. Tests: happy path, double booking rejected, cancel frees slot, validation.

## 6. Appointment dashboard and reporting
1. Volt component: upcoming and past appointments with status, filterable by agent, therapist, therapy.
2. Simple report: appointment counts by agent and by therapy.
3. Add summary tiles to the dashboard.
4. Tests: listing, filters, report totals, empty states.

## 7. Confirmation notifications
1. `AppointmentBooked` mailable with a witty, warm tone.
2. Send on booking when the agent has an email; log mailer in dev.
3. Tests with `Mail::fake()`: sent when email present, skipped when absent.

## 8. Wrap-up
1. Responsive pass at 320, 390, 768, 1024, 1280px; apply `.touch-target`; add viewport and responsive-class feature tests.
2. Update seeders and `README.md` (new pages, mail log).
3. Update `specs/roadmap.md` checkboxes for delivered items; add `CHANGELOG.md` entry.
4. Run `sail test` and `sail composer test:coverage` (100%); fix gaps.
5. Complete `validation.md` and merge `mvp` into `main`.
