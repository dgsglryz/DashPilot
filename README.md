# 🎯 DashPilot - WordPress/Shopify Operations Dashboard

**Production-ready operations dashboard for web agencies managing 100+ client sites with real-time visibility, automated remediation, and multi-channel notifications.**

---

## 📋 Table of Contents

- [Why DashPilot?](#why-dashpilot)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Testing](#testing)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)

---

## 🤔 Why DashPilot?

Built for boutique web agencies managing large WordPress and Shopify portfolios. DashPilot centralizes uptime monitoring, SEO scoring, alerting, task management, and client reporting into a single, responsive control center.

**Key Highlights:**

- ✅ Modular monolith architecture (easily extractable to microservices)
- ✅ Redis caching + queue processing for scalability
- ✅ WordPress REST API + Shopify REST/GraphQL integrations
- ✅ Real-time health monitoring with automated alerts
- ✅ Email + Webhook notifications (Slack, Discord, custom)
- ✅ Production-ready: 85%+ test coverage, CI/CD pipeline

---

## ✨ Features

### Site Management

- WordPress REST API integration with health monitoring
- Shopify Admin API (REST metrics + GraphQL analytics)
- Automated health checks every 5 minutes via Redis queue
- SSL certificate expiry tracking
- CSV/Excel exports

### Analytics & Reporting

- Real-time dashboard with Chart.js visualizations
- SEO analysis engine (6 scoring signals)
- Performance metrics (response time, uptime trends)
- PDF client reports + CSV exports

### Notifications

- Email alerts (critical/high issues) via queued Mailables
- Webhook integrations (Slack blocks, Discord embeds, custom endpoints)
- Alert assignment, acknowledgement, and resolution flows

### Client & Task Management

- Client CRM with reports tab
- Kanban board for tasks (drag/drop, filters, assignments)
- Team messaging system

### User Experience

- Global command palette (Cmd/Ctrl + K)
- Dark mode toggle, responsive layouts, keyboard shortcuts
- Shopify Liquid editor with syntax highlighting + snippet library

---

## 🛠 Tech Stack

| Layer              | Technology           | Purpose                       |
| ------------------ | -------------------- | ----------------------------- |
| **Backend**        | Laravel 11           | PHP framework (Inertia-ready) |
|                    | PHP 8.2              | Runtime                       |
|                    | MySQL 8.0            | Primary database              |
|                    | Redis 7              | Cache + queue + session       |
| **Frontend**       | Vue 3                | SPA (Composition API)         |
|                    | Inertia.js           | Server-driven SPA adapter     |
|                    | TailwindCSS          | Utility-first styling         |
|                    | Chart.js             | Data visualization            |
| **APIs**           | WordPress REST       | Health + plugin data          |
|                    | Shopify REST/GraphQL | Orders/products/analytics     |
| **Infrastructure** | Docker Compose       | Multi-container stack         |
|                    | GitHub Actions       | CI/CD pipeline                |
| **Dev Tools**      | What The Diff        | AI-powered code review        |
|                    | SonarCloud           | Code quality + security       |

---

## 🚀 Quick Start

### Prerequisites

- Docker Desktop 24+ (with Docker Compose v2)
- Node.js 20.19+
- Git 2.40+

### Installation

```bash
# 1. Clone repository
git clone https://github.com/dogusguleryuz/DashPilot.git
cd DashPilot

# 2. Copy environment file
cp .env.example .env

# 3. Launch Docker services
docker-compose up -d

# 4. Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# 5. Generate application key
docker-compose exec app php artisan key:generate

# 6. Run migrations + seed demo data
docker-compose exec app php artisan migrate --seed

# 7. Build frontend assets
docker-compose exec app npm run build

# 8. Start queue workers
docker-compose exec app php artisan queue:work --queue=health-checks,emails,webhooks --tries=3
```

### Access Services

- **Dashboard**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8080
- **MailHog**: http://localhost:8025

**Default Credentials:**

```
Email: demo@dashpilot.test
Password: Password123
```

---

## 🏗 Architecture

### Modular Monolith Structure

```
app/
├── Modules/
│   ├── Sites/            # WordPress & Shopify integrations
│   ├── Alerts/           # Rule engine, acknowledgements
│   ├── Notifications/    # Email + webhook delivery
│   ├── Clients/          # CRM + reporting
│   ├── Tasks/            # Kanban + assignments
│   ├── SEO/              # Scoring service
│   ├── Reports/          # PDF + CSV generation
│   └── Shopify/          # Liquid editor + services
├── Shared/               # Traits, helpers, services
└── Http/                 # Middleware, controllers
```

### Key Components

**Health Check System:**

- Scheduler dispatches `CheckSiteHealth` job every 5 minutes
- Jobs processed via Redis queue
- Results cached in Redis (5-min TTL)
- Alerts created when thresholds breached

**Notification System:**

- Email notifications via queued `SendEmailNotification` job
- Webhooks delivered through `DeliverWebhook` job with retry logic
- HMAC signatures for webhook security

**Caching Strategy:**

- Dashboard stats: 60s TTL
- WordPress health: 5min TTL
- Shopify analytics: 10min TTL
- SEO scores: 1hr TTL

---

## 🧪 Testing

```bash
# Backend tests
docker-compose exec app php artisan test

# Frontend lint
docker-compose exec app npm run lint

# E2E tests (Playwright)
npm run test:e2e
```

**Test Coverage:** 85%+ backend + E2E tests covering Sites, Alerts, Notifications, SEO, Shopify modules.

---

## 🚢 Deployment

### Production Checklist

```bash
# Configure environment
cp .env.example .env.production
# Set APP_ENV=production, APP_DEBUG=false, database/redis creds

# Install optimized dependencies
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build

# Optimize Laravel caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Start queue workers (Supervisor)
php artisan queue:work --queue=health-checks,emails,webhooks --tries=3
```

---

## 🔧 Environment Variables

### Required Variables

```env
APP_NAME=DashPilot
APP_ENV=local
APP_KEY=base64:xxx  # Generated via: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=dashpilot
DB_USERNAME=dashpilot
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Mail (MailHog for local)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@dashpilot.test
```

### Site-Specific API Credentials

Stored in `sites` table (not `.env`):

**WordPress Sites:**

- `wp_api_url`: Full URL to WordPress REST endpoint
- `wp_api_key`: Optional bearer token

**Shopify Sites:**

- `shopify_store_url`: Store domain
- `shopify_access_token`: Admin API access token

---

## 🐛 Troubleshooting

### Queue not processing

```bash
docker-compose exec app php artisan queue:work --queue=health-checks,emails,webhooks --verbose
```

### Redis connectivity errors

```bash
docker-compose exec redis redis-cli ping
docker-compose exec app php artisan cache:clear
```

### Database connection refused

```bash
docker-compose logs db
docker-compose restart db
sleep 10
docker-compose exec app php artisan migrate --force
```

### WordPress/Shopify API failures

- Verify API credentials in `sites` table
- Check Telescope logs at `/telescope`
- Update secrets if expired

### Tests failing

```bash
docker-compose exec app php artisan migrate:fresh --seed --env=testing
npm run build
npm run test:e2e
```

---

## 📊 Docker Services

| Service     | Container              | Port       | URL                     |
| ----------- | ---------------------- | ---------- | ----------------------- |
| Laravel App | `dashpilot-app`        | 8000       | http://localhost:8000   |
| MySQL       | `dashpilot-db`         | 13306→3306 | mysql://localhost:13306 |
| Redis       | `dashpilot-redis`      | 6379       | redis://localhost:6379  |
| phpMyAdmin  | `dashpilot-phpmyadmin` | 8080       | http://localhost:8080   |
| MailHog     | `dashpilot-mailhog`    | 8025       | http://localhost:8025   |

---

## 🔐 Security Features

- ✅ Laravel Breeze authentication (session + email verification)
- ✅ Authorization policies on all modules
- ✅ CSRF protection (Inertia automatic)
- ✅ XSS protection via Blade escaping
- ✅ SQL injection defense (Eloquent ORM)
- ✅ API key encryption (Laravel encrypted casts)
- ✅ Rate limiting on search/API endpoints
- ✅ HTTPS enforcement in production

---

## 📈 Performance

**Benchmarks** (MacBook Pro M1, 16GB RAM):

- Dashboard load: 280ms (cached)
- Sites list (125 items): 450ms
- Health check processing: 10 sites/sec
- Redis cache hit rate: 82%
- Queue throughput: 120 jobs/min

---

## 🛣 Roadmap

**Completed ✅**

- Real-time health monitoring
- WordPress & Shopify integrations
- Alerting pipeline (email + webhooks)
- Client/Task management
- Liquid editor
- Dark mode, command palette

**Planned 🚧**

- Mobile companion app
- Advanced reporting
- Multi-language localization
- SSO via OAuth2
- Automated plugin/theme updates

---

## 📝 Contributing

1. Fork repository & create feature branch
2. Follow PSR-12, strict types, English-only comments
3. Write tests before opening PR
4. Run `php artisan test`, `npm run lint`, `npm run build`
5. Open PR with summary and testing notes

---

## 📄 License

Released under the [MIT License](LICENSE).

---

## 🙏 Acknowledgments

- Laravel, Vue, Inertia, and Tailwind communities
- Shopify & WordPress ecosystem maintainers
- SonarCloud + GitHub Actions for CI tooling
- What The Diff for AI-powered code review

---

## 📧 Contact & Support

- **Issues**: [GitHub Issues](https://github.com/dogusguleryuz/DashPilot/issues)
- **Documentation**: https://github.com/dogusguleryuz/DashPilot/wiki
