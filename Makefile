PHP     = docker compose exec php
CONSOLE = $(PHP) php bin/console

.PHONY: help init rebuild up restart stop remove seed-products test test-unit lint cs cs-fix stan deptrac phpmd analyse k6-smoke k6-load k6-stress

## —— Help ————————————————————————————————————————————————————————————————————
help: ## Show this help
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

## —— Docker ——————————————————————————————————————————————————————————————————
init: ## First-time setup: copy .env, build images, run migrations
	cp .env.example .env
	docker compose up --build -d
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

rebuild: ## Rebuild Docker images
	docker compose stop
	docker compose up --build -d

up: ## Start all services in background
	docker compose up -d

restart: ## Restart all services
	docker compose up --recreate -d

stop: ## Stop all services (keeps volumes)
	docker compose stop

remove: ## Stop and remove all containers, volumes and images
	docker compose down --volumes --rmi all

## —— Database ————————————————————————————————————————————————————————————————
seed-products: ## Import 357 Siroko products into MySQL
	$(CONSOLE) app:seed:siroko-products

## —— Tests ———————————————————————————————————————————————————————————————————
test: ## Run all PHPUnit tests
	$(PHP) php vendor/bin/phpunit

test-with-coverage: ## Run all PHPUnit tests with coverage
	$(PHP) php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text

## —— Code quality —————————————————————————————————————————————————————————————
lint: ## Check PHP syntax errors
	$(PHP) php -l src

cs: ## Check code style (PHP CS Fixer, dry-run)
	$(PHP) php vendor/bin/php-cs-fixer check --diff

cs-fix: ## Fix code style automatically
	$(PHP) php vendor/bin/php-cs-fixer fix

stan: ## Run PHPStan static analysis (level 8)
	$(PHP) php -d memory_limit=512M vendor/bin/phpstan analyse

deptrac: ## Check layer dependencies (Domain / Application / Infrastructure)
	$(PHP) php vendor/bin/deptrac analyse --config-file=deptrac.yaml

phpmd: ## Run PHP Mess Detector
	$(PHP) php vendor/bin/phpmd src text phpmd.xml

analyse: cs stan deptrac phpmd ## Run all static analysis tools

## —— Performance —————————————————————————————————————————————————————————————
k6-smoke: ## Smoke test: 1 VU, 10 iterations across all endpoints
	docker compose --profile k6 run --rm k6 run /scripts/smoke.js

k6-load: ## Load test: 10 VUs for 60s on GET /products/search
	docker compose --profile k6 run --rm -e BASE_URL=http://nginx k6 run --out json=/scripts/results/load.json /scripts/load.js

k6-stress: ## Stress test: ramp 1 to 50 VUs on GET /products/search
	docker compose --profile k6 run --rm -e BASE_URL=http://nginx k6 run --out json=/scripts/results/stress.json /scripts/stress.js
