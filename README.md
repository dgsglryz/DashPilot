# DashPilot

Operations dashboard for agencies managing 100+ WordPress / Shopify installations. The stack is Laravel 11, Breeze (Vue 3 + Inertia + Tailwind), MySQL, Redis, Docker, and GitHub Actions.

## Latest Updates

- **Nov 15** – WordPress health integration added (`WordPressService` + `CheckSiteHealth` job + Redis caching).
- **Nov 15** – ESLint flat config (`npm run lint`) enforces Vue 3 + TS conventions in CI.

## Tech Stack

- Laravel 11, PHP 8.2, MySQL 8, Redis 7 (cache + queues), Docker Compose
- Vue 3 (script setup), Inertia.js, TailwindCSS, Chart.js, Heroicons
- Laravel Breeze auth, Laravel Scheduler/Queues, MailHog for SMTP testing
- GitHub Actions (`.github/workflows/ci.yml`) runs composer/npm install, build, tests, and optional webhook/email notifications

## Setup

```bash
cp .env.example .env
composer install
npm install
docker-compose up -d --build
php artisan key:generate
docker-compose exec app php artisan migrate --seed
npm run build
```

Key environment variables:

| Variable | Purpose | Default |
| --- | --- | --- |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection (Docker service `db`) | `db`, `3306`, `dashpilot`, `dashpilot`, `secret` |
| `QUEUE_CONNECTION` | Uses Redis queues for health checks & notifications | `redis` |
| `MAIL_HOST`, `MAIL_PORT` | Points to MailHog for local email previews | `mailhog`, `1025` |
| `WORDPRESS_HTTP_TIMEOUT` | Timeout (seconds) for WordPress REST calls | `10` |

## Running the App

- `docker-compose up -d` – spins up PHP-FPM, MySQL, Redis, MailHog, phpMyAdmin.
- `docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000` (or use `php artisan serve` locally) – serves Inertia SPA.
- `docker-compose exec app php artisan queue:work --queue=health-checks,default` – processes queued jobs such as `CheckSiteHealth`.

## Testing & Linting

```bash
docker-compose exec app php artisan test   # 25+ feature/unit tests
npm run lint                               # ESLint (Vue 3 + TS)
```

CI runs both commands plus Vite build; failing lint/tests block the pipeline.

## WordPress Health Integration

- `app/Modules/Sites/Services/WordPressService` pulls `/wp-json/dashpilot/v1/health` with optional bearer token, caches the payload in Redis for 5 minutes, and normalizes plugin/theme/version data.
- `app/Modules/Sites/Jobs/CheckSiteHealth` dispatches on the `health-checks` queue, invokes the service, stores a `SiteCheck` record, and updates `Site::health_score` / `last_checked_at`.
- Configure each site’s `wp_api_url` + `wp_api_key` (if required) in the database; the job will skip invalid entries via domain exception handling.

## Useful Commands

```bash
docker-compose exec app php artisan migrate:fresh --seed   # rebuild DB with demo data
docker-compose exec app php artisan schedule:run           # trigger scheduled checks/manual cron
docker-compose exec app php artisan queue:work --tries=3   # recommended queue worker profile
```

## License

MIT – see `LICENSE`.
