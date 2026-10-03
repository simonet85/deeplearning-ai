# AgentClinic

## Input from stakeholders

- Mary in engineering wants a reliable site with a popular stack based on TypeScript, giving agents and staff a dashboard for easy access.
- Susan in product has a set of features about agents and their ailments, therapies, and booking appointments.
- Steve in marketing wants an attractive site that works well with a modern browser.

## Getting started

Built with Laravel, Livewire (Volt) and Postgres, run in Docker via [Laravel Sail](https://laravel.com/docs/sail). Only Docker is required on the host.

```sh
cp .env.example .env
docker run --rm -v "$(pwd):/opt" -w /opt laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Then open http://localhost. On Windows, run `sail` from WSL, or use `docker compose` with `WWWUSER`/`WWWGROUP` set.

Seeded staff (password `password`): `admin@agentclinic.test` (admin), `sam@agentclinic.test` and `riley@agentclinic.test` (therapists). Registration is disabled; only staff log in.

Common commands: `sail test`, `sail artisan migrate:fresh --seed`, `sail down`.
