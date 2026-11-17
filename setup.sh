#!/bin/bash
# ==============================================================================
# One-Click Automated Assessment Launch Script
#
# ❓ INTHA SCRIPT ENNA PANNUTHU?
# Evaluator / Interviewer laptop-la zero manual setup-oda:
# 1. Docker containers-ah build & launch pannum.
# 2. Database migrations & seeders run pannum.
# 3. 33 Automated Feature Tests-um pass aagi green-la kaatum!
# 4. Live URL-ah ready panni tharum.
# ==============================================================================

set -e

# Terminal Colors for Clean Output:
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}===================================================================${NC}"
echo -e "${CYAN}🚀 Launching Mini Subscription Management System (Full-Stack Docker)${NC}"
echo -e "${BLUE}===================================================================${NC}"

# 1. Docker check:
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}❌ Docker is not installed. Please install Docker & Docker Compose first.${NC}"
    exit 1
fi

# Auto-sync local Stripe credentials into .env.docker if available:
if [ -f .env ] && grep -q "STRIPE_SECRET=sk_" .env; then
    LOCAL_KEY=$(grep "^STRIPE_KEY=" .env | cut -d '=' -f2-)
    LOCAL_SECRET=$(grep "^STRIPE_SECRET=" .env | cut -d '=' -f2-)
    LOCAL_WEBHOOK=$(grep "^STRIPE_WEBHOOK_SECRET=" .env | cut -d '=' -f2-)
    [ -n "$LOCAL_KEY" ] && sed -i "s|^STRIPE_KEY=.*|STRIPE_KEY=${LOCAL_KEY}|" .env.docker
    [ -n "$LOCAL_SECRET" ] && sed -i "s|^STRIPE_SECRET=.*|STRIPE_SECRET=${LOCAL_SECRET}|" .env.docker
    [ -n "$LOCAL_WEBHOOK" ] && sed -i "s|^STRIPE_WEBHOOK_SECRET=.*|STRIPE_WEBHOOK_SECRET=${LOCAL_WEBHOOK}|" .env.docker
fi

# 2. Build and Launch Containers in Background:
echo -e "\n${BLUE}📦 Step 1: Building & Starting Docker Containers (App, MySQL 8, Redis)...${NC}"
docker compose up -d --build

# 3. Wait for MySQL to become healthy:
echo -e "\n${BLUE}⏳ Step 2: Waiting for MySQL database container to initialize...${NC}"
until [ "$(docker inspect -f {{.State.Health.Status}} mini_sub_mysql 2>/dev/null)" == "healthy" ]; do
    echo -e "   Waiting for database connection (5s)..."
    sleep 5
done
echo -e "${GREEN}   ✅ MySQL Database is fully initialized and healthy!${NC}"

# 4. Run Migrations & Plan Seeders inside App container:
echo -e "\n${BLUE}🗄️ Step 3: Running Database Migrations & Seeding Initial Plans...${NC}"
docker compose exec app php artisan migrate:fresh --seed --force

# 5. Execute Complete Automated Test Suite (Isolated in-memory SQLite):
echo -e "\n${BLUE}🧪 Step 4: Running 33 Automated Feature Tests inside Docker...${NC}"
docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e CACHE_STORE=array -e SESSION_DRIVER=array app php artisan test

# 6. Display Success Banner:
echo -e "\n${GREEN}===================================================================${NC}"
echo -e "${GREEN}🎉 CONGRATULATIONS! ALL 33 TESTS PASSED & SYSTEM IS 100% LIVE!${NC}"
echo -e "${GREEN}===================================================================${NC}"
echo -e "${CYAN}🌐 Application URL  : ${YELLOW}http://localhost:8000${NC}"
echo -e "${CYAN}📊 Frontend SPA     : ${YELLOW}React 18 + Vite + Tailwind (Bundled)${NC}"
echo -e "${CYAN}⚙️ Backend REST API  : ${YELLOW}Laravel 13 on PHP 8.3-FPM + Nginx${NC}"
echo -e "${CYAN}📮 Postman Suite    : ${YELLOW}mini-subscription.postman_collection.json${NC}"
echo -e "${GREEN}===================================================================${NC}"
echo -e "To view live logs: ${CYAN}docker compose logs -f app${NC}"
echo -e "To stop containers: ${CYAN}docker compose down${NC}\n"
