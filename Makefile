DC := docker compose
APP := $(DC) exec app

.PHONY: up down build setup install test cs cs-fix sa clean logs health shell db-shell mcp-server

## ── Lifecycle ──────────────────────────────────────────────

up: ## Start all containers
	$(DC) up -d

down: ## Stop all containers
	$(DC) down

build: ## Build images
	$(DC) build

setup: .env up ## Install deps + migrate DB
	$(APP) composer install
	$(APP) php bin/setup.php
	@echo "Ready: http://localhost:$${PORT:-8080}"

.env:
	cp .env.example .env

install: ## Install composer dependencies
	$(APP) composer install

## ── Development ────────────────────────────────────────────

test: ## Run PHPUnit
	$(APP) ./vendor/bin/phpunit

cs: ## Check coding standards
	$(APP) ./vendor/bin/phpcs

cs-fix: ## Fix coding standards
	$(APP) ./vendor/bin/phpcbf src tests

sa: ## Static analysis
	$(APP) ./vendor/bin/psalm --show-info=true
	$(APP) ./vendor/bin/phpstan analyse -c phpstan.neon

clean: ## Clear caches
	$(APP) rm -rf ./var/tmp/*.php

## ── Utilities ──────────────────────────────────────────────

logs: ## Tail container logs
	$(DC) logs -f

health: ## Health check
	curl -sf http://localhost:$${PORT:-8080}/health | python3 -m json.tool

shell: ## Open a shell in the app container
	$(APP) bash

db-shell: ## Open psql
	$(DC) exec db psql -U $${DB_USER:-app} constraint_engine

mcp-server: ## Start MCP server (stdio)
	$(APP) php bin/mcp-server.php

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

.DEFAULT_GOAL := help
