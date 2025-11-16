# DashPilot

Operations dashboard for agencies managing 100+ WordPress / Shopify installations. The stack is Laravel 11, Breeze (Vue 3 + Inertia + Tailwind), MySQL, Redis, Docker, and GitHub Actions.

## Latest Updates

- **Nov 16** – Dashboard overview now showcases featured site cards plus revamped site detail pages (hero imagery, logos, SEO/alert/timeline panels) with global search suggestions.
- **Nov 16** – Demo seeder now loads **125 production-like sites** (unique industries, thumbnails, alerts, tasks, reports) for instant dashboard testing.
- **Nov 15** – WordPress health integration added (`WordPressService` + `CheckSiteHealth` job + Redis caching).
- **Nov 15** – Shopify REST + GraphQL services provide cached store metrics for dashboard cards.
- **Nov 15** – SEOService computes on-page score (meta/H1/SSL/speed/viewport/alt) with Redis caching.
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

| Variable                                                          | Purpose                                             | Default                                          |
| ----------------------------------------------------------------- | --------------------------------------------------- | ------------------------------------------------ |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection (Docker service `db`)              | `db`, `3306`, `dashpilot`, `dashpilot`, `secret` |
| `QUEUE_CONNECTION`                                                | Uses Redis queues for health checks & notifications | `redis`                                          |
| `MAIL_HOST`, `MAIL_PORT`                                          | Points to MailHog for local email previews          | `mailhog`, `1025`                                |
| `WORDPRESS_HTTP_TIMEOUT`                                          | Timeout (seconds) for WordPress REST calls          | `10`                                             |
| `SHOPIFY_API_VERSION`                                             | REST/GraphQL version path for Shopify Admin API     | `2024-10`                                        |
| `SHOPIFY_HTTP_TIMEOUT`                                            | Timeout (seconds) for Shopify HTTP requests         | `10`                                             |
| `SEO_MOCK_ENDPOINT`                                               | Temporary endpoint returning mock SEO payloads      | `https://dashpilot.mock/api/seo`                 |

## Running the App

- `docker-compose up -d` – spins up PHP-FPM, MySQL, Redis, MailHog, phpMyAdmin.
- `docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000` (or use `php artisan serve` locally) – serves Inertia SPA.
- `docker-compose exec app php artisan queue:work --queue=health-checks,default` – processes queued jobs such as `CheckSiteHealth`.
- Demo login: `demo@dashpilot.test / Password123`

## Demo Data (125 Sites)

- `database/seeders/DatabaseSeeder` now generates 125 sites across ecommerce, hospitality, healthcare, finance, education, media, and SaaS verticals.
- Every site now receives curated Unsplash hero images plus branded logos (DiceBear/Clearbit) alongside uptime/load metrics, SiteChecks, alerts, tasks, reports, and recent activity so the dashboard/alerts/metrics pages feel real at a glance.
- Run **any time** to refresh data:

```bash
# Local or within Docker
php artisan migrate:fresh --seed
# Docker helper
docker-compose exec app php artisan migrate:fresh --seed
```

- Data is written to MySQL (Docker service `db`); inspect/edit via phpMyAdmin at `http://localhost:8080`.

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

## Shopify REST & GraphQL Integration

- `app/Modules/Shopify/Services/ShopifyRestService` calls `/admin/api/<version>/shop.json`, `orders.json`, and `products/count.json` (with `X-Shopify-Access-Token`) to return cached overview metrics for each site.
- `app/Modules/Shopify/Services/ShopifyGraphQLService` issues the nested analytics query (products + variants + recent orders) and caches the GraphQL payload for 10 minutes.
- Both services throw `ShopifyApiException` when credentials (`shopify_store_url`, `shopify_access_token`) are missing or the remote responds with errors—ideal for future jobs/alerts.

## SEO Scoring

- `app/Modules/SEO/Services/SEOService` runs a lightweight audit that checks meta description, H1 count, SSL, page speed, viewport meta, and missing image alt tags. Each issue deducts from 100 with caps noted in `.cursorrules`.
- Results are cached for 1 hour per site via Redis. `tests/Unit/SEO/Services/SEOServiceTest` covers issue detection and cache behavior.

## Useful Commands

```bash
docker-compose exec app php artisan migrate:fresh --seed   # rebuild DB with demo data
docker-compose exec app php artisan schedule:run           # trigger scheduled checks/manual cron
docker-compose exec app php artisan queue:work --tries=3   # recommended queue worker profile
```

## License

MIT – see `LICENSE`.
