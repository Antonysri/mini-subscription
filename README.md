# Mini Subscription Management System (Laravel 13, MySQL, Redis, JWT, Stripe)

A production-grade **Mini Subscription Management System** built with **PHP 8.3+**, **Laravel 13**, **MySQL 8.x**, **Redis**, **JWT Authentication**, and **Stripe Payment Gateway** (Checkout Sessions & Webhooks).

---

## 🚀 Key Features

1. **JWT Stateless Authentication**: User Registration, Login, Protected Profile (`/me`), and Secure Logout.
2. **Plans Management with Redis Caching**: Active subscription plans cached with 10-minute TTL and invalidation triggers.
3. **Stripe Checkout Integration**: Secure checkout sessions without exposing trusted amounts to the client.
4. **Idempotent Webhook Processing**: Verified Stripe signatures, atomic database transactions, duplicate event protection.
5. **Subscription & Payment History**: Active subscription tracking, expiration calculation, and payment logs.
6. **Robust REST Architecture**: Controller-Service pattern, Form Request validation, standardized JSON responses.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 13 (PHP 8.3+)
- **Database**: MySQL 8.x
- **Cache**: Redis
- **Auth**: JWT (JSON Web Tokens)
- **Payments**: Stripe PHP SDK (Test Mode Checkout)
- **API Testing**: Postman Collection

---

## 📋 Project Roadmap & Issues

- [x] Issue #1: Environment & Tooling Setup (PHP 8.3+, Composer, Redis, Laravel 13)
- [x] Issue #2: Database Architecture, Migrations & Plan Seeders
- [x] Issue #3: JWT Authentication System (Register, Login, Me, Logout)
- [ ] Issue #4: Redis Caching for Subscription Plans
- [ ] Issue #5: Stripe Checkout Session Creation
- [ ] Issue #6: Stripe Webhooks & Subscription Activation
- [ ] Issue #7: Payment History & Active Subscription Status APIs
- [ ] Issue #8: Security Hardening, Postman Collection & Documentation
- [ ] Issue #9: Free Cloud Deployment & Public Tunneling (Render/Koyeb, Aiven MySQL, Upstash Redis, Ngrok)

