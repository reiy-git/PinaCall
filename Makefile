# ==============================================================================
# Makefile - University Portal System
# Supports: Docker mode (Standard/Production-like) and Local Dev mode (No Docker)
# ==============================================================================

.PHONY: help up down restart logs build migrate seed test clean dev dev-api dev-frontend install

# Default target
help:
	@echo "========================================================================"
	@echo "                   UNIVERSITY PORTAL SYSTEM - MAKEFILE                  "
	@echo "========================================================================"
	@echo "Docker Workflow:"
	@echo "  make up            - Start all containers in detached mode"
	@echo "  make down          - Stop and remove all containers"
	@echo "  make restart       - Restart all containers"
	@echo "  make logs          - Tail container logs"
	@echo "  make build         - Build/rebuild container images"
	@echo "  make migrate       - Run database migrations inside API container"
	@echo "  make seed          - Seed the database without resetting it (empty DB only)"
	@echo "  make test          - Run isolated backend tests"
	@echo ""
	@echo "Local Development Workflow (Direct host machine execution):"
	@echo "  make install       - Install backend (composer) and frontend (npm) dependencies"
	@echo "  make dev           - Run migrations and start both API & Frontend locally"
	@echo "  make dev-api       - Start Laravel development server (http://localhost:8000)"
	@echo "  make dev-frontend  - Start Vite development server (http://localhost:5173)"
	@echo "  make clean         - Clean up cached files and temporary artifacts"
	@echo "========================================================================"

# --- Docker Targets ---

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

build:
	docker compose build

migrate:
	docker compose exec api php artisan migrate

seed:
	docker compose exec api php artisan db:seed --force

test:
	docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= -e SESSION_DRIVER=array -e CACHE_STORE=array -e QUEUE_CONNECTION=sync api php artisan test

# --- Local Host Targets ---

install:
	cd api && composer install
	cd frontend && npm install

dev-api:
	cd api && php artisan serve --host=0.0.0.0 --port=8000

dev-frontend:
	cd frontend && npm run dev

dev:
	@echo "Starting local environment..."
	cd api && php artisan migrate --force
	@echo "Tip: Run 'make dev-api' in one terminal and 'make dev-frontend' in another."

clean:
	cd api && php artisan optimize:clear
	@echo "Cleaned backend optimization caches."
