# Plan: Foundation & Core Dashboard

## 1. Project scaffolding
1. Remove the leftover TypeScript scaffold (`package.json`, `package-lock.json`, `tsconfig.json`, `src/`).
2. Create the Laravel app in this directory.
3. Add `docker-compose.yml` with a Postgres service; configure `.env` and `.env.example`.
4. Install Livewire and run the initial migrations against the Docker database.

## 2. Authentication
1. Install Laravel Breeze (Livewire stack).
2. Add a `role` column to `users` (`admin`, `therapist`) with a default.
3. Add role helpers on `User` and a middleware or gate for staff-only routes.

## 3. Domain models
1. Migration, model, and factory for `Agent` (name, agent_type, bio).
2. Confirm `User` fields match the data model.

## 4. Seed data
1. Seed one admin and a few therapists.
2. Seed a set of sample agents with satirical bios.

## 5. Dashboard
1. Livewire dashboard landing page behind auth: welcome, navigation, agent list.
2. Apply brand tone and responsive layout.

## 6. Tests and wrap-up
1. PHPUnit feature tests: login, role access, dashboard rendering, seeders.
2. Update `README.md` with setup instructions.
