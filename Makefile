## Orenza Backend — управление Docker-окружением.
## Все команды выполняются в контейнере app, поэтому PHP на хосте не нужен.

DC := docker compose
APP := $(DC) exec app
APP_RUN := $(DC) run --rm --no-deps app

# UID/GID хоста прокидываются в сборку, чтобы файлы, созданные контейнером
# (миграции, кеши, логи), оставались доступны на хосте.
export UID := $(shell id -u)
export GID := $(shell id -g)

.DEFAULT_GOAL := help
.PHONY: help init up down restart build rebuild ps logs logs-app shell root-shell \
        composer artisan tinker migrate migrate-fresh seed fresh rollback \
        test test-pg lint lint-check check psql redis queue-restart mail clean

help: ## Показать список команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

init: ## Первый запуск: .env, сборка образов, миграции и сиды
	@test -f .env || cp .env.example .env
	$(DC) build
	$(DC) up -d
	$(APP) php artisan migrate --seed --force
	@echo "\nAPI:      http://localhost:$${APP_PORT:-8080}/api/v1/meta/enums"
	@echo "Mailpit:  http://localhost:$${MAILPIT_UI_PORT:-8025}"

up: ## Поднять окружение
	$(DC) up -d

down: ## Остановить окружение
	$(DC) down

restart: ## Перезапустить контейнеры
	$(DC) restart

build: ## Собрать образы
	$(DC) build

rebuild: ## Пересобрать образы без кеша и поднять заново
	$(DC) build --no-cache
	$(DC) up -d --force-recreate

ps: ## Статус контейнеров
	$(DC) ps

logs: ## Логи всех сервисов
	$(DC) logs -f --tail=100

logs-app: ## Логи PHP-контейнера
	$(DC) logs -f --tail=100 app

shell: ## Bash внутри контейнера app
	$(APP) bash

root-shell: ## Bash внутри контейнера app от root
	$(DC) exec -u root app bash

composer: ## composer c=<команда>, напр. make composer c="require laravel/horizon"
	$(APP) composer $(c)

artisan: ## artisan c=<команда>, напр. make artisan c="make:model Post -m"
	$(APP) php artisan $(c)

tinker: ## Запустить tinker
	$(APP) php artisan tinker

migrate: ## Применить миграции
	$(APP) php artisan migrate

migrate-fresh: ## Пересоздать схему без сидов
	$(APP) php artisan migrate:fresh

seed: ## Выполнить сиды
	$(APP) php artisan db:seed

fresh: ## Пересоздать схему и залить сиды
	$(APP) php artisan migrate:fresh --seed

rollback: ## Откатить последнюю миграцию
	$(APP) php artisan migrate:rollback

test: ## Тесты на SQLite in-memory (быстро)
	$(APP) php artisan test

test-pg: ## Тесты на PostgreSQL (база orenza_test)
	$(APP) vendor/bin/phpunit -c phpunit.pgsql.xml

lint: ## Pint: отформатировать код
	$(APP) vendor/bin/pint

lint-check: ## Pint: проверить форматирование
	$(APP) vendor/bin/pint --test

check: lint-check test ## Проверка перед коммитом

psql: ## Консоль PostgreSQL
	$(DC) exec postgres psql -U $${DB_USERNAME:-orenza} -d $${DB_DATABASE:-orenza}

redis: ## Консоль Redis
	$(DC) exec redis redis-cli

queue-restart: ## Перезапустить воркеры очереди
	$(APP) php artisan queue:restart

mail: ## Открыть Mailpit
	@open http://localhost:$${MAILPIT_UI_PORT:-8025} 2>/dev/null || echo "http://localhost:$${MAILPIT_UI_PORT:-8025}"

clean: ## Остановить всё и удалить тома (данные БД будут потеряны)
	$(DC) down -v --remove-orphans
