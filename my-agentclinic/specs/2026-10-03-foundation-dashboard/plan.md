# Plan: Foundation & Core Dashboard

## 1. Laravel Sail scaffolding
Laravel Sail (the official Docker environment) provides PHP, Composer, Node and Postgres in containers; nothing is required on the host except Docker.
1. Remove the leftover TypeScript scaffold (`package.json`, `package-lock.json`, `tsconfig.json`, `src/`). (Done.)
2. Remove the hand-written `Dockerfile`, `docker-compose.yml` and `.dockerignore`; a custom image build failed on a flaky network and Sail replaces it.
3. Scaffold Laravel with Sail and the `pgsql` service by running the `laravelsail/php84-composer` image (`laravel new`, `composer require laravel/sail --dev`, `php artisan sail:install --with=pgsql`) in a temp folder, then merge it into the repo root (the root is not empty). On Windows Git Bash, set `MSYS_NO_PATHCONV=1` so `-w /opt` is not rewritten.
4. Review the generated `compose.yaml` and `.env.example` (`DB_CONNECTION=pgsql`, `DB_HOST=pgsql`); extend `.gitignore`; keep `.env` out of git.
5. Start with `./vendor/bin/sail up -d`, install Livewire, run the initial migrations against the Postgres container, and confirm the welcome page loads at http://localhost.
6. Document `sail up`, `sail artisan ...` and `sail test` in `README.md`.

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
