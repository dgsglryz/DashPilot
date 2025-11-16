# DashPilot

Operations dashboard for agencies managing 100+ WordPress / Shopify installations. The stack is Laravel 11, Breeze (Vue 3 + Inertia + Tailwind), MySQL, Redis, Docker, and GitHub Actions.

## Latest Updates

- **Nov 16** – **DEBUGGING & LOGGING SYSTEM**: Comprehensive logging infrastructure:
  - ✅ **Laravel Telescope** - Full request/query/job/exception tracking with web UI (`/telescope`)
  - ✅ **LoggingService** - Structured logging for API calls, jobs, services, controllers, exceptions
  - ✅ **Backend Logging**:
    - Exception Logging - All exceptions automatically logged with full context (file, line, trace, user, IP)
    - API Request/Response Logging - WordPress, Shopify REST, Shopify GraphQL, SEO API calls with duration tracking
    - Job Execution Logging - All queued jobs (CheckSiteHealth, SendEmailNotification, DeliverWebhook) logged
    - Email Notification Logging - Email send attempts logged with success/failure status
    - Webhook Delivery Logging - Webhook attempts with retry tracking and duration
    - Controller Action Logging - All controller actions logged (when debug mode enabled)
    - Service Method Logging - Service method calls tracked for debugging
    - Slow Query Detection - Queries >100ms automatically logged
    - AlertObserver Logging - Alert creation/resolution events logged
    - Inertia Error Logging - Inertia middleware errors logged
  - ✅ **Frontend Logging**:
    - JavaScript Error Handler - Global error handler for all JS errors
    - Vue Error Handler - Vue component errors with component name and props
    - Inertia Error Handler - Inertia page errors logged
    - Unhandled Promise Rejections - Promise rejections automatically logged
    - HTTP Error Interceptor - Axios interceptor logs 4xx/5xx errors (except 401/403)
    - Frontend Error API - `/api/log-frontend-error` endpoint receives all frontend errors
- **Nov 16** – **COMPLETE FEATURE SET**: All features fully integrated and production-ready:
  - ✅ Command Palette (Cmd+K) - Global search with autocomplete and keyboard navigation
  - ✅ Toast Notifications - Beautiful, non-intrusive feedback system
  - ✅ Skeleton Loaders - Professional loading states
  - ✅ Empty States - Beautiful empty state components
  - ✅ Activity Log Enhancements - User avatars (DiceBear), CSV export, real-time feed (30s polling)
  - ✅ Dashboard Charts - Doughnut chart (sites by status), Bar chart (alert frequency), Top 5 problematic sites table
  - ✅ Keyboard Shortcuts - Cmd+K, Cmd+/, G+D, G+S, G+A
  - ✅ Export Features - CSV/Excel export for Sites and Alerts with filters
  - ✅ Health Score Modal - Detailed score breakdown with info icon
  - ✅ Quick Actions Dropdown - Contextual actions for sites table (view, health check, copy URL, favorite)
  - ✅ Favorites/Pinned Sites - Star icon toggle, Dashboard pinned section, backend support
  - ✅ Breadcrumbs Navigation - Fully integrated on Dashboard, Sites, Alerts, Show pages
  - ✅ Enhanced Progress Bar - Better visual feedback with Inertia Progress
  - ✅ Batch Operations - Multi-select checkboxes, bulk health check, export selected
  - ✅ Email Preview/Test - Settings page with template selector (alert-created, alert-resolved, daily-digest)
  - ✅ Webhook Test Console - Settings page with payload editor, sample loader, response viewer
  - ✅ Recent Viewed Items - Sidebar section with localStorage tracking (last 5 pages)
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
- **Telescope UI**: Visit `http://localhost:8000/telescope` to view requests, queries, jobs, exceptions, and logs in real-time.
- Demo login: `demo@dashpilot.test / Password123`

## Debugging & Logging

### Laravel Telescope
Telescope provides a beautiful debugging interface for your application:
- **Access**: `http://localhost:8000/telescope`
- **Features**: 
  - Request/Response tracking
  - Database queries with bindings
  - Queued jobs execution
  - Exceptions with stack traces
  - Log entries
  - Cache operations
  - Mail sent/received
  - Events dispatched

### LoggingService
Custom structured logging service (`app/Shared/Services/LoggingService.php`) provides:
- **API Request/Response Logging**: All WordPress, Shopify REST, Shopify GraphQL, SEO API calls logged with duration
- **Job Execution Logging**: All queued jobs logged (started, completed, failed)
- **Email Notification Logging**: Email send attempts with success/failure
- **Webhook Delivery Logging**: Webhook attempts with retry tracking
- **Exception Logging**: All exceptions logged with full context (file, line, trace, user, IP, URL)
- **Controller Action Logging**: All controller actions logged (when `APP_DEBUG=true` or `LOG_CONTROLLER_ACTIONS=true`)
- **Service Method Logging**: Service method calls tracked
- **Slow Query Detection**: Queries >100ms automatically logged
- **AlertObserver Logging**: Alert creation/resolution events logged

### Frontend Error Logging
Frontend errors are automatically captured and sent to the backend:
- **JavaScript Errors**: Global `error` event listener
- **Vue Component Errors**: Vue error handler with component name and props
- **Inertia Errors**: Inertia page errors logged
- **Unhandled Promise Rejections**: Promise rejection handler
- **HTTP Errors**: Axios interceptor logs 4xx/5xx errors (except auth errors 401/403)
- **Error Endpoint**: `/api/log-frontend-error` receives all frontend errors and logs them via LoggingService

### Log Files
- **Location**: `storage/logs/laravel.log`
- **Daily Rotation**: Logs rotate daily (keeps 14 days by default)
- **Log Levels**: `debug`, `info`, `warning`, `error`, `critical`

### Environment Variables
```env
# Enable Telescope (default: true)
TELESCOPE_ENABLED=true

# Telescope path (default: telescope)
TELESCOPE_PATH=telescope

# Log level (default: debug)
LOG_LEVEL=debug

# Enable controller action logging
LOG_CONTROLLER_ACTIONS=false
```

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
