#!/usr/bin/env bash
#
# Готовит контейнер к работе: зависимости, ключ приложения, права,
# ожидание БД и (для роли app) миграции. Затем передаёт управление CMD.
set -euo pipefail

readonly ROLE="${CONTAINER_ROLE:-app}"

log() {
    printf '\033[0;36m[entrypoint:%s]\033[0m %s\n' "${ROLE}" "$1"
}

cd /var/www/html

if [[ ! -f .env && -f .env.example ]]; then
    log '.env не найден — копирую из .env.example'
    cp .env.example .env
fi

if [[ ! -f vendor/autoload.php ]]; then
    log 'устанавливаю composer-зависимости'
    composer install --no-interaction --prefer-dist
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

if [[ -f .env ]] && ! grep -qE '^APP_KEY=.+' .env; then
    log 'генерирую APP_KEY'
    php artisan key:generate --force --no-interaction
fi

# Ждём готовности PostgreSQL: без этого первые миграции падают на гонке старта.
# Адрес берём из DB_WAIT_*, а не из DB_HOST: переменные с именами конфигурации
# приложения в окружении контейнера ломали бы подмену настроек в phpunit.xml.
if [[ -n "${DB_WAIT_HOST:-}" ]]; then
    log "жду PostgreSQL на ${DB_WAIT_HOST}:${DB_WAIT_PORT:-5432}"
    for attempt in $(seq 1 60); do
        if pg_isready --host="${DB_WAIT_HOST}" --port="${DB_WAIT_PORT:-5432}" --username="${DB_WAIT_USER:-postgres}" --quiet; then
            break
        fi
        if [[ "${attempt}" -eq 60 ]]; then
            log 'PostgreSQL не поднялся за 60 секунд'
            exit 1
        fi
        sleep 1
    done
    log 'PostgreSQL готов'
fi

# Миграции и прогрев кешей нужны только при старте самого сервиса, а не при
# разовых запусках вида `docker compose run --rm app php artisan ...`.
if [[ "${ROLE}" == 'app' && "${1:-}" == 'php-fpm' ]]; then
    if [[ "${AUTO_MIGRATE:-false}" == 'true' ]]; then
        log 'применяю миграции'
        php artisan migrate --force --no-interaction
    fi

    if [[ "${APP_ENV:-local}" == 'production' ]]; then
        log 'кеширую конфигурацию, маршруты и события'
        php artisan optimize
    else
        php artisan optimize:clear >/dev/null 2>&1 || true
    fi
fi

log "запускаю: $*"
exec "$@"
