# Mini Subscription Management System (Laravel 13, MySQL, Redis, JWT, Stripe)

A production-grade, enterprise **Mini Subscription Management System** built with **PHP 8.3+**, **Laravel 13**, **MySQL 8.x**, **Redis**, **JWT Authentication (tymon/jwt-auth)**, and **Stripe Payment Gateway** (Checkout Sessions & Webhook Idempotency).

---

## 🚀 Key Features

1. **JWT Stateless Authentication**: User Registration, Login, Protected Profile (`/me`), and Blacklist Logout.
2. **Plans Management with Redis Caching**: Active subscription plans cached with 10-minute TTL and invalidation triggers.
3. **Stripe Checkout Integration (2026 Enterprise DI)**: Secure Stripe Checkout Session generation without exposing client-side pricing.
4. **Idempotent Webhook Processing**: Cryptographic HMAC signature verification (`stripe-signature`), pessimistic row locking (`lockForUpdate`), atomic DB transactions, and duplicate event defense.
5. **Subscription & Payment History**: Active subscription tracking, expiration calculation (`days_remaining`), and paginated payment audit logs.
6. **OWASP API Security Hardening**: Global security headers (`nosniff`, `DENY` frames, `X-XSS-Protection`, `HSTS`, `Referrer-Policy`).
7. **Tiered Rate Limiting**:
   - `throttle:auth`: 5 requests/min per IP/email for brute-force defense on `/api/register` and `/api/login`.
   - `throttle:api`: 60 requests/min per user on all protected routes.
8. **Centralized Error Envelopes**: Uniform JSON error responses for 401 Unauthorized, 404 Not Found, and 422 Validation errors.

---

## 🛠️ Tech Stack

- **Backend Framework**: Laravel 13 (PHP 8.3+)
- **Frontend SPA**: React 18, Vite, TypeScript, Tailwind CSS, TanStack Query v5, Redux Toolkit
- **Database**: MySQL 8.x
- **Cache & Key-Value Store**: Redis
- **Auth Provider**: JSON Web Tokens (`php-open-source-saver/jwt-auth`)
- **Payments**: Stripe PHP SDK (`stripe/stripe-php: ^23.0`)
- **Testing**: PHPUnit / Pest Feature Test Suite (33 tests, 152 assertions)
- **API Tooling**: Postman Collection v2.1

---

## 📋 Project Roadmap & Milestone Status

- [x] **Issue #1**: Environment & Tooling Setup (PHP 8.3+, Composer, Redis, Laravel 13)
- [x] **Issue #2**: Database Architecture, Migrations & Plan Seeders
- [x] **Issue #3**: JWT Authentication System (Register, Login, Me, Logout)
- [x] **Issue #4**: Redis Caching for Subscription Plans (10m TTL & Cache Invalidation)
- [x] **Issue #5**: Stripe Checkout Session Creation (IoC Dependency Injection & Pending Payments)
- [x] **Issue #6**: Stripe Webhooks & Idempotent Subscription Activation (HMAC & Row Locking)
- [x] **Issue #7**: Active Subscription Status & Paginated Payment History APIs
- [x] **Issue #8**: Security Hardening, Tiered Rate Limiting & Postman Suite
- [x] **Issue #10**: Modern React + Vite + TypeScript Frontend SPA (Pricing, Auth, Dashboard & Customer Portal)
- [x] **Issue #9**: Production Dockerization, One-Click `setup.sh`, GitHub Actions CI/CD Pipeline & Live Webhook Tunneling

---

## 🐳 Quickstart & Docker Deployment (One-Click Setup)

The entire full-stack system (Frontend SPA, Backend API, MySQL 8, and Redis) is fully containerized with automated health checks.

### Option 1: Automated One-Click Launch (Recommended for Evaluators)

Run the included automated setup script:
```bash
chmod +x setup.sh
./setup.sh
```

**What `./setup.sh` does automatically:**
1. Builds multi-stage Docker container (Node 20 compiles React ➔ PHP 8.3-FPM + Nginx serves API).
2. Starts MySQL 8 and Redis 7 service containers in the background.
3. Polls MySQL healthcheck until database is 100% ready.
4. Executes database migrations and seeds initial subscription plans.
5. Executes all **33 Automated Feature Tests** (100% test coverage) inside the container.
6. Presents the live URL: `http://localhost:8000`.

---

### Option 2: Manual Docker Compose Execution

```bash
# 1. Build and start containers in background
docker compose up -d --build

# 2. Run migrations and plan seeders inside the app container
docker compose exec app php artisan migrate:fresh --seed --force

# 3. Run automated test suite inside Docker
docker compose exec app php artisan test

# 4. View live logs
docker compose logs -f app

# 5. Stop containers
docker compose down
```

---

## ⚡ Stripe Real-Time Webhook Tunneling (Local Development)

Because Stripe cloud servers cannot directly send webhooks to `http://localhost:8000`, use Stripe CLI or Ngrok to forward live events:

```bash
# Option A: Stripe CLI (Fastest & Recommended)
stripe listen --forward-to localhost:8000/api/stripe/webhook

# Option B: Ngrok Tunnel
ngrok http 8000
# Update STRIPE_WEBHOOK_SECRET in .env.docker and restart
```

---

## 📦 How to Create Clean Submission Zip (Interview Standard)

When submitting this project as a zip file, exclude heavy machine-dependent folders:

```bash
zip -r mini-subscription.zip . \
  -x "node_modules/*" \
  -x "frontend/node_modules/*" \
  -x "vendor/*" \
  -x ".git/*" \
  -x "frontend/dist/*" \
  -x "storage/logs/*" \
  -x ".env"
```

---

## 📡 API Endpoints Reference

| Method | Endpoint | Auth Required | Rate Limit | Description |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/register` | No | 5 req/min (`throttle:auth`) | Register a new user and receive JWT |
| `POST` | `/api/login` | No | 5 req/min (`throttle:auth`) | Authenticate user and receive JWT |
| `GET` | `/api/plans` | No | None | Fetch all subscription plans (Redis cached) |
| `POST` | `/api/stripe/webhook` | Stripe HMAC | None | Webhook listener for Stripe events |
| `GET` | `/api/me` | Yes (`Bearer`) | 60 req/min (`throttle:api`) | Get current authenticated user details |
| `POST` | `/api/logout` | Yes (`Bearer`) | 60 req/min (`throttle:api`) | Blacklist current JWT token |
| `POST` | `/api/checkout` | Yes (`Bearer`) | 60 req/min (`throttle:api`) | Generate Stripe Checkout Session URL |
| `GET` | `/api/me/subscription` | Yes (`Bearer`) | 60 req/min (`throttle:api`) | Get active subscription & days remaining |
| `GET` | `/api/me/payments` | Yes (`Bearer`) | 60 req/min (`throttle:api`) | Get paginated user payment records |

---

## 🧪 Automated Testing Suite

To run the complete automated test suite (Unit & Feature tests):

```bash
php artisan test
```

### Test Coverage Highlights:
- **`AuthTest`**: Registration, validation failure, login, bad credentials, `/me` profile retrieval, unauthenticated 401 guard, and token blacklisting on logout.
- **`PlanTest`**: Redis cache miss/hit verification, 10-minute TTL verification, and cache invalidation on plan updates.
- **`CheckoutTest`**: StripeClient mock injection, checkout session URL generation, pending payment creation, and invalid plan rejection.
- **`WebhookTest`**: Genuine Stripe HMAC cryptographic signature generation, `checkout.session.completed` event processing, subscription activation, duplicate event idempotency, and invalid signature rejection.
- **`BillingTest`**: Unauthenticated guard verification, graceful null handling for unsubscribed users, active subscription calculations (`days_remaining`), paginated payment history, and multi-tenant data isolation.
- **`SecurityAndRateLimitTest`**: OWASP security headers validation, 5 req/min brute-force throttle triggering HTTP 429, and centralized 401/404 JSON envelopes.

---

## 📬 Postman Collection

Import the included file into Postman:
```
mini-subscription.postman_collection.json
```

- **Collection Variable `baseUrl`**: Defaults to `http://127.0.0.1:8000/api`
- **Auto JWT Capture**: When calling `Register` or `Login`, test scripts automatically extract `data.access_token` and store it in `{{bearer_token}}` for all protected endpoints!
