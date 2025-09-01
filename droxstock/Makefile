# DroxStock Makefile for Development and Production

.PHONY: help
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: setup
setup: ## Initial setup for development
	cp env.production.example .env
	docker-compose build
	docker-compose up -d
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate --seed
	docker-compose exec app php artisan storage:link
	@echo "✅ Setup complete! Visit http://localhost"

.PHONY: up
up: ## Start all containers
	docker-compose up -d

.PHONY: down
down: ## Stop all containers
	docker-compose down

.PHONY: restart
restart: down up ## Restart all containers

.PHONY: build
build: ## Build containers
	docker-compose build --no-cache

.PHONY: logs
logs: ## Show container logs
	docker-compose logs -f

.PHONY: shell
shell: ## Access app container shell
	docker-compose exec app sh

.PHONY: tinker
tinker: ## Run Laravel tinker
	docker-compose exec app php artisan tinker

.PHONY: migrate
migrate: ## Run migrations
	docker-compose exec app php artisan migrate

.PHONY: seed
seed: ## Run seeders
	docker-compose exec app php artisan db:seed

.PHONY: fresh
fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

.PHONY: test
test: ## Run tests
	docker-compose exec app vendor/bin/pest

.PHONY: test-coverage
test-coverage: ## Run tests with coverage
	docker-compose exec app vendor/bin/pest --coverage

.PHONY: cache-clear
cache-clear: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

.PHONY: cache
cache: ## Cache config, routes, and views
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

.PHONY: queue
queue: ## Start queue worker
	docker-compose exec app php artisan queue:work

.PHONY: schedule
schedule: ## Run scheduler
	docker-compose exec app php artisan schedule:run

.PHONY: docs
docs: ## Generate API documentation
	docker-compose exec app php artisan scramble:export
	docker-compose exec app php artisan app:generate-focused-api-docs

.PHONY: clean
clean: ## Clean up Docker resources
	docker-compose down -v
	docker system prune -af

.PHONY: backup
backup: ## Backup database
	mkdir -p backups
	docker-compose exec -T db pg_dump -U droxstock droxstock | gzip > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql.gz
	@echo "✅ Database backed up to backups/"

.PHONY: restore
restore: ## Restore database from latest backup
	@read -p "Enter backup filename: " file; \
	gunzip -c backups/$$file | docker-compose exec -T db psql -U droxstock droxstock
	@echo "✅ Database restored"

.PHONY: deploy
deploy: ## Deploy to production
	./scripts/deploy.sh

.PHONY: rollback
rollback: ## Rollback deployment
	./scripts/rollback.sh
