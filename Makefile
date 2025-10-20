.PHONY: help up down build restart logs clean test lint ci install setup

# Default target
help: ## Show this help message
	@echo "Task Management API - Available Commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Docker commands
up: ## Start all services
	docker compose up -d

down: ## Stop all services
	docker compose down

build: ## Build all Docker images
	docker compose build --no-cache

restart: ## Restart all services
	docker compose restart

logs: ## Show logs from all services
	docker compose logs -f

logs-laravel: ## Show Laravel logs only
	docker compose logs -f laravel-app

logs-node: ## Show Node.js logs only
	docker compose logs -f node-notify

clean: ## Clean up containers, volumes, and images
	docker compose down -v --remove-orphans
	docker system prune -f

# Installation
install: ## Install dependencies for both services
	@echo "Installing Laravel dependencies..."
	docker compose exec laravel-app composer install
	@echo "Installing Node.js dependencies..."
	docker compose exec node-notify npm install

setup: up ## Initial setup (up + install + migrate + seed)
	@echo "Waiting for services to be ready..."
	sleep 10
	@echo "Copying .env files..."
	cp laravel/.env.example laravel/.env || true
	cp node-notify/.env.example node-notify/.env || true
	@echo "Installing dependencies..."
	docker compose exec laravel-app composer install
	docker compose exec node-notify npm install
	@echo "Generating Laravel app key..."
	docker compose exec laravel-app php artisan key:generate
	@echo "Running migrations..."
	docker compose exec laravel-app php artisan migrate:fresh --seed
	@echo "✅ Setup complete! API: http://localhost:8000 | Node: http://localhost:3001"

# Laravel commands
migrate: ## Run database migrations
	docker compose exec laravel-app php artisan migrate

migrate-fresh: ## Fresh migration with seeding
	docker compose exec laravel-app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker compose exec laravel-app php artisan db:seed

tinker: ## Open Laravel Tinker
	docker compose exec laravel-app php artisan tinker

artisan: ## Run artisan command (e.g., make artisan route:list)
	docker compose exec laravel-app php artisan $(filter-out $@,$(MAKECMDGOALS))

# Testing
test: ## Run all tests (Laravel + Node.js)
	@echo "Running Laravel tests..."
	docker compose exec laravel-app php artisan test
	@echo "Running Node.js tests..."
	docker compose exec node-notify npm test

test-laravel: ## Run Laravel tests only
	docker compose exec laravel-app php artisan test

test-node: ## Run Node.js tests only
	docker compose exec node-notify npm test

test-coverage: ## Run tests with coverage
	docker compose exec laravel-app php artisan test --coverage
	docker compose exec node-notify npm run test

# Code quality
lint: ## Run code linting (Pint + ESLint)
	@echo "Running Laravel Pint..."
	docker compose exec laravel-app ./vendor/bin/pint
	@echo "Running ESLint..."
	docker compose exec node-notify npm run lint:fix

lint-check: ## Check code style without fixing
	docker compose exec laravel-app ./vendor/bin/pint --test
	docker compose exec node-notify npm run lint

analyse: ## Run static analysis (PHPStan)
	docker compose exec laravel-app ./vendor/bin/phpstan analyse

ci: lint-check analyse test ## Run CI pipeline (lint + analyse + test)

# Database
db-shell: ## Connect to PostgreSQL shell
	docker compose exec postgres psql -U taskuser -d task_management

redis-cli: ## Connect to Redis CLI
	docker compose exec redis redis-cli

# Shell access
shell-laravel: ## Access Laravel container shell
	docker compose exec laravel-app sh

shell-node: ## Access Node.js container shell
	docker compose exec node-notify sh

# Utility
fresh: down build setup ## Complete fresh start

status: ## Show status of all services
	docker compose ps

%:
	@:
