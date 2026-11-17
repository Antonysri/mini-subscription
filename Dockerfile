# ==============================================================================
# 🌟 STAGE 1: FRONTEND BUILDER (Node.js 20 Alpine)
# 
# ❓ INTHA STAGE ENNA PANNUTHU?
# 1. React + TypeScript + Tailwind CSS code-ah compile pannum.
# 2. Production-optimized static assets (HTML, CSS, JS) generate panni /dist-la vekkum.
# ==============================================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app/frontend

# Package files mattum munnadi copy panni install panrom (Docker Layer Caching):
COPY frontend/package*.json ./
RUN npm install

# Full frontend source code copy panni production build compile panrom:
COPY frontend/ ./
RUN npm run build


# ==============================================================================
# 🌟 STAGE 2: BACKEND RUNTIME (PHP 8.3-FPM + Nginx on Alpine Linux)
# 
# ❓ INTHA STAGE ENNA PANNUTHU?
# 1. Lightweight Linux OS (Alpine) edukkum.
# 2. PHP 8.3, Nginx, Supervisor, Composer, MySQL & Redis extensions install pannum.
# 3. Stage 1-la compile panna React UI-yum, Laravel API-yum merge pannum!
# ==============================================================================
FROM php:8.3-fpm-alpine

# 1. System packages & build dependencies install panrom:
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    ca-certificates \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    linux-headers \
    $PHPIZE_DEPS

# 2. Essential PHP Extensions install & enable panrom:
# - pdo_mysql: MySQL database connection-kku
# - bcmath: Stripe currency math & cents calculations-kku
# - opcache: PHP code-ah memory-la cache panni 3x speed aakka
# - redis: Redis cache & session driver-kku (via PECL)
RUN docker-php-ext-install pdo_mysql bcmath opcache && \
    pecl install redis && \
    docker-php-ext-enable redis

# 3. Official Composer binary-ah container kulla copy panrom:
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 4. Working directory set panrom:
WORKDIR /var/www/html

# 5. Composer dependencies caching (including dev tools like PHPUnit for running tests in container):
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# 6. Entire Laravel backend source code-ah copy panrom:
COPY . .
RUN cp .env.docker .env

# 7. 🌟 THE MAGIC MERGE:
# Stage 1-la create aana React production 'dist' files-ah
# Laravel-oda public directory-kulla copy panrom!
COPY --from=frontend-builder /app/frontend/dist/ /var/www/html/public/

# 8. Optimized autoloader generate panni, storage permissions set panrom:
RUN composer dump-autoload --optimize && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Nginx & Supervisor config files-ah replace panrom:
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# 10. Container veliya expose panna vendiya Web port:
EXPOSE 80

# 11. Container start aana odane Supervisor (Nginx + PHP-FPM) launch aagum:
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
