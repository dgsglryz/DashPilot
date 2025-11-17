# DashPilot

Operations dashboard for agencies managing 100+ WordPress / Shopify installations. The stack is Laravel 11, Breeze (Vue 3 + Inertia + Tailwind), MySQL, Redis, Docker, and GitHub Actions.

## Latest Updates

- **Nov 16** – **NODE.JS VERSION UPDATE**: Updated to Node.js 20.19+ requirement for Vite 7 compatibility:
    - ✅ **package.json** - Updated engines to require Node.js >=20.19.0 and npm >=10.0.0
    - ✅ **Dockerfile** - Updated to install Node.js 20 LTS via NodeSource repository
    - ✅ **GitHub Actions** - Updated workflows to use `.nvmrc` file for consistent Node.js version
    - ✅ **.nvmrc** - Updated to specify Node.js 20.19.0
    - ⚠️ **Action Required**: Local developers must upgrade to Node.js 20.19+ (run `nvm use` if using nvm)
- **Nov 16** – **SONARCLOUD INTEGRATION**: Code quality analysis and security scanning:
    - ✅ **SonarCloud CI Integration** - Automated code quality scans on every push/PR
    - ✅ **Security Hotspot Detection** - Identifies potential security vulnerabilities
    - ✅ **Bug Detection** - Finds code defects that could cause runtime errors
    - ✅ **Code Smell Analysis** - Maintainability issue detection
    - ✅ **PR Integration** - Automatic comments on pull requests with quality gate status
    - ✅ **Dashboard** - Viewable code quality metrics and issue tracking
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
- **Node.js 20.19+** (required for Vite 7), npm 10+
- Vue 3 (script setup), Inertia.js, TailwindCSS, Chart.js, Heroicons
- Laravel Breeze auth, Laravel Scheduler/Queues, MailHog for SMTP testing
- GitHub Actions (`.github/workflows/ci.yml`) runs composer/npm install, build, tests, SonarCloud scan, and optional webhook/email notifications
- SonarCloud integration for code quality analysis and security hotspot detection

## Prerequisites

- **Node.js 20.19+** (check with `node --version`)
- **npm 10+** (check with `npm --version`)
- PHP 8.2+
- Docker & Docker Compose
- Composer

**Note:** If you're using `nvm`, run `nvm use` to switch to the version specified in `.nvmrc` (20.19.0).

## Setup

```bash
# Ensure correct Node.js version (if using nvm)
nvm use

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

### Backend Tests

```bash
docker-compose exec app php artisan test   # 25+ feature/unit tests
```

### Frontend Linting

```bash
npm run lint                               # ESLint (Vue 3 + TS)
```

### E2E Tests (Playwright)

**ÖNEMLİ:** E2E testlerini çalıştırmadan önce uygulamanın çalışıyor olması gerekiyor:

1. Docker container'ları başlatın: `docker-compose up -d`
2. Veritabanını hazırlayın: `docker-compose exec app php artisan migrate:fresh --seed`
3. Laravel server'ı başlatın: `docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000`

Ya da otomatik setup script'i kullanın:
```bash
./scripts/setup-e2e-tests.sh
```

**Test Kullanıcısı:**
- Email: `admin@test.com`
- Password: `password`

Bu kullanıcı `DatabaseSeeder` tarafından otomatik olarak oluşturulur.

Detaylı bilgi için: [tests/e2e/README.md](tests/e2e/README.md)

Comprehensive end-to-end testing with Playwright covering all admin workflows:

```bash
# Install Playwright browsers (first time only)
npx playwright install chromium

# Run all E2E tests
npm run test:e2e

# Run with UI mode (interactive)
npm run test:e2e:ui

# Run in headed mode (see browser)
npm run test:e2e:headed

# Debug mode (step through tests)
npm run test:e2e:debug

# View test report
npm run test:e2e:report
```

**Test Coverage:**

- ✅ Authentication (login, logout, session persistence)
- ✅ Dashboard (stats cards, charts, navigation)
- ✅ Sites Management (CRUD, search, filters, health checks)
- ✅ Alerts (view, filter, resolve, acknowledge)
- ✅ Clients (CRUD, reports)
- ✅ Tasks (Kanban board, create, move, edit, delete)
- ✅ Settings (profile, email, webhooks, password)

**Test Files:**

- `tests/e2e/auth.spec.js` - Authentication flows
- `tests/e2e/dashboard.spec.js` - Dashboard functionality
- `tests/e2e/sites.spec.js` - Sites management
- `tests/e2e/alerts.spec.js` - Alerts management
- `tests/e2e/clients.spec.js` - Clients management
- `tests/e2e/tasks.spec.js` - Tasks management
- `tests/e2e/settings.spec.js` - Settings management

**Test Helpers:**

- `tests/e2e/helpers/auth.js` - Login/logout utilities
- `tests/e2e/helpers/navigation.js` - Navigation helpers
- `tests/e2e/helpers/wait.js` - Wait utilities for UI elements

**Configuration:**

- `playwright.config.js` - Playwright configuration
- Base URL: `http://localhost:8000` (configurable via `APP_URL` env)
- Default test timeout: 30 seconds
- Screenshots/videos on failure
- HTML reporter with detailed results

**Prerequisites:**

1. Docker containers running (`docker-compose up -d`)
2. Database seeded (`php artisan migrate:fresh --seed`)
3. Test user created (default: `admin@test.com` / `password`)
4. Laravel server running on port 8000

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

## SonarCloud Code Quality

DashPilot uses SonarCloud for automated code quality analysis, security hotspot detection, and bug identification.

### Setup

1. **Create SonarCloud Account & Project:**
   - Go to [sonarcloud.io](https://sonarcloud.io) and sign in with GitHub
   - Create a new project for your repository
   - Note your **Organization Key** and **Project Key**

2. **Configure GitHub Secrets:**
   - Go to your GitHub repository → Settings → Secrets and variables → Actions
   - Add `SONAR_TOKEN` secret with your SonarCloud token (found in SonarCloud → My Account → Security)

3. **Update `sonar-project.properties`:**
   - Update `sonar.organization` with your organization key
   - Update `sonar.projectKey` if different from default

4. **CI Integration:**
   - SonarCloud scan runs automatically on every push/PR via `.github/workflows/ci.yml`
   - The scan job runs after tests complete (even if tests fail)
   - Results are available in SonarCloud dashboard and as PR comments

### Viewing Results

- **Dashboard**: Visit your SonarCloud project dashboard to see:
  - Code quality metrics (maintainability, reliability, security)
  - Security hotspots (potential vulnerabilities)
  - Bugs (code defects)
  - Code smells (maintainability issues)
  - Coverage reports (if configured)

- **PR Integration**: SonarCloud automatically comments on PRs with:
  - New issues introduced
  - Quality gate status
  - Coverage changes

### Fixing Issues

After the first scan, review the SonarCloud dashboard for:
1. **Security Hotspots** - Potential security vulnerabilities (priority: high)
2. **Bugs** - Code defects that could cause runtime errors (priority: high)
3. **Code Smells** - Maintainability issues (priority: medium)

Export issues from SonarCloud and provide them to Cursor AI for automated fixes.

## Useful Commands

```bash
docker-compose exec app php artisan migrate:fresh --seed   # rebuild DB with demo data
docker-compose exec app php artisan schedule:run           # trigger scheduled checks/manual cron
docker-compose exec app php artisan queue:work --tries=3   # recommended queue worker profile
```

## License

MIT – see `LICENSE`.

---

**Test Note:** This PR is created to verify What The Diff integration is working correctly.
