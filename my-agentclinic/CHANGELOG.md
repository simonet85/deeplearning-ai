# Changelog

All notable changes to AgentClinic, grouped by date (newest first).
Maintained with the `changelog` skill; run it before merging.

<!-- changelog-last-commit: 6b2e9e0 -->

## 2026-10-04

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
