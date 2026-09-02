# Docker-окружение

## Состав

| Сервис      | Образ                | Наружу               | Назначение                                   |
|-------------|----------------------|----------------------|----------------------------------------------|
| `web`       | nginx:1.29-alpine    | `${APP_PORT}` → 8080 | Веб-сервер, проксирует PHP на `app:9000`     |
| `app`       | сборка `docker/php`  | —                    | PHP-FPM 8.5, точка входа для artisan/composer |
| `postgres`  | postgres:18-alpine   | `5432`               | Основная БД + база `orenza_test` для тестов  |
| `redis`     | redis:8-alpine       | `6379`               | Кеш, сессии, очередь                          |
| `queue`     | сборка `docker/php`  | —                    | `queue:work` — обработчик очереди             |
| `scheduler` | сборка `docker/php`  | —                    | `schedule:work` — планировщик                 |
| `mailpit`   | axllent/mailpit      | `8025`               | Перехват исходящей почты (SMTP на 1025)       |

Тома: `postgres-data`, `redis-data`. Код монтируется в контейнеры как bind-mount,
поэтому изменения видны сразу, без пересборки образа.

## Запуск

```bash
make init     # .env, сборка, up, миграции и сиды — первый запуск
make up       # поднять
make down     # остановить
make ps       # статус
make logs     # логи всех сервисов
make help     # весь список команд
```

После `make init`: API — <http://localhost:8080/api/v1>, почта — <http://localhost:8025>.

Без make всё то же самое доступно напрямую:

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose exec app bash
```

## Где какая конфигурация

Настройки приложения читаются **только из `.env`**, и адреса в нём — контейнерные
(`DB_HOST=postgres`, `REDIS_HOST=redis`, `MAIL_HOST=mailpit`).

В `compose.yaml` переменные приложения намеренно не задаются. Причина: в CLI PHP
кладёт переменные окружения в `$_SERVER`, а Laravel читает их раньше, чем `$_ENV`
и `.env`. Такие переменные перекрыли бы значения из `phpunit.xml` — и `artisan test`
внутри контейнера пошёл бы по базе разработки и очистил её. Поэтому контейнеру
передаётся только служебное: `CONTAINER_ROLE`, `AUTO_MIGRATE`, `XDEBUG_MODE`
и `DB_WAIT_*` (адрес для ожидания готовности БД в entrypoint).

По той же причине в `phpunit.xml` соединение с БД зафиксировано двумя способами:
`<env force="true">` и `<server>` — последний перекрывает даже переменные окружения.

Порты и параметры сборки настраиваются в `.env`: `APP_PORT`, `DB_FORWARD_PORT`,
`REDIS_FORWARD_PORT`, `MAILPIT_UI_PORT`, `PHP_VERSION`, `APP_BUILD_TARGET`,
`AUTO_MIGRATE`, `XDEBUG_MODE`.

## Тесты

```bash
make test      # SQLite in-memory — быстрый прогон (0.4 с)
make test-pg   # PostgreSQL, база orenza_test — паритет с production
```

База `orenza_test` создаётся скриптом `docker/postgres/init` при первичной
инициализации тома. Если меняете `DB_DATABASE`, поправьте имя базы и в
`phpunit.pgsql.xml`.

## Образ

`docker/php/Dockerfile` — многостадийный:

- `base` — PHP-FPM + расширения (`pdo_pgsql`, `redis`, `intl`, `bcmath`, `opcache`,
  `pcntl`, `zip`, `sockets`, `pdo_sqlite` для быстрых тестов), composer, entrypoint;
- `development` — то же + Xdebug (выключен, включается `XDEBUG_MODE=debug`) и OPcache
  с проверкой таймстемпов; код приходит через bind-mount;
- `vendor` — отдельный слой с production-зависимостями (кешируется по composer.lock);
- `production` — код внутри образа, `composer dump-autoload --classmap-authoritative`,
  OPcache без revalidate + JIT, `php artisan optimize` при старте.

UID/GID процесса выравниваются с хостовым пользователем (build-args `UID`/`GID`,
`make` подставляет их автоматически) — файлы, созданные в контейнере, остаются
доступны на хосте.

Сборка production-образа:

```bash
docker build -f docker/php/Dockerfile --target production -t orenza/app:prod .
```

## Xdebug

```bash
# .env
XDEBUG_MODE=debug     # или coverage / profile
make restart
```

Слушатель IDE — порт 9003, `host.docker.internal`, ключ `PHPSTORM`.

## Частые операции

```bash
make artisan c="make:model Post -m"
make composer c="require laravel/horizon"
make fresh              # migrate:fresh --seed
make psql               # консоль PostgreSQL
make redis              # консоль Redis
make queue-restart      # перезапустить воркеры после деплоя кода
make shell              # bash внутри app
make clean              # down -v: удалит тома вместе с данными
```

## Диагностика

- **`make up` падает на unhealthy postgres** — посмотрите `docker compose logs postgres`.
  Если том создавался старой версией образа, поможет `make clean` (данные будут потеряны).
- **Порт занят** — измените `APP_PORT` / `DB_FORWARD_PORT` в `.env` и выполните `make up`.
- **Изменения в `.env` не применяются** — конфигурация кешируется только в production;
  в dev выполните `make artisan c=optimize:clear`.
- **artisan с хоста** (без Docker) — замените в `.env` `postgres`/`redis`/`mailpit`
  на `127.0.0.1`; порты проброшены наружу.
