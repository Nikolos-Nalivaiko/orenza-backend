# Orenza Backend

Каркас REST API на Laravel 13 (PHP 8.5): слои **Controller → Service → Action → Repository → Model**,
DTO для передачи данных, Enum'ы для доменных состояний, единый формат ответов и ошибок,
Sanctum для токенов. Окружение — Docker: PostgreSQL 18, Redis 8, nginx, воркер очереди,
планировщик и Mailpit.

Доменного кода пока нет — есть базовые классы, конвенции и генераторы под них.

## Быстрый старт

```bash
make init     # .env, сборка образов, миграции и сиды
```

| Что             | Адрес                            |
|-----------------|----------------------------------|
| API             | <http://localhost:8080/api/v1>   |
| Health-check    | <http://localhost:8080/up>       |
| Почта (Mailpit) | <http://localhost:8025>          |
| PostgreSQL      | `localhost:5432`, база `orenza`  |
| Redis           | `localhost:6379`                 |

Подробно об окружении — в [docs/DOCKER.md](docs/DOCKER.md).

## Команды

```bash
make help               # весь список
make up / down / ps     # управление окружением
make logs               # логи всех сервисов
make shell              # bash внутри контейнера app
make artisan c="..."    # artisan-команда, напр. c="make:model Post -m"
make composer c="..."   # composer-команда
make migrate / fresh    # миграции; fresh = migrate:fresh --seed
make psql / redis       # консоли БД и Redis
make test               # тесты на SQLite in-memory (быстро)
make test-pg            # тесты на PostgreSQL (база orenza_test)
make lint / lint-check  # Pint
make check              # lint-check + test
```

Генераторы слоёв:

```bash
make artisan c="make:action Posts/CreatePostAction"
make artisan c="make:service PostService"
make artisan c="make:data Posts/PostData"
make artisan c="make:repository Post --model=Post"   # контракт + Eloquent-реализация
make artisan c="make:enum PostStatus --string"
```

## Структура

```
docker/                   Dockerfile (php-fpm), конфиги nginx/PHP, init-скрипты postgres
compose.yaml              сервисы: web, app, postgres, redis, queue, scheduler, mailpit
Makefile                  команды окружения (make help)

app/
├── Actions/Contracts/    маркер Action + конвенции для бизнес-операций
├── Console/Commands/     генераторы make:action / make:service / make:data / make:repository
├── DataTransferObjects/  BaseData: snake_case + Optional-сентинел для PATCH
├── Enums/                Concerns/InteractsWithEnum, Contracts/HasLabel, SortDirection
├── Exceptions/           DomainException и BusinessRuleException (сами рендерят JSON)
├── Http/
│   ├── Controllers/      базовый Controller (AuthorizesRequests, ValidatesRequests)
│   ├── Middleware/       ForceJsonResponse
│   └── Requests/         ApiFormRequest — единый конверт ошибок валидации
├── Models/
├── Providers/            AppServiceProvider, RepositoryServiceProvider (карта биндингов)
├── Repositories/
│   ├── Contracts/        RepositoryInterface
│   └── Eloquent/         BaseRepository (CRUD, пагинация, applyCriteria)
├── Services/             оркестрация use-case'ов
└── Support/              ApiResponse, Optional
```

Описание слоёв, правил и версионирования — в [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Формат ответов

Успех:

```json
{ "data": { "...": "..." }, "message": "Готово.", "meta": { "current_page": 1 } }
```

Ошибка:

```json
{ "message": "The given data was invalid.", "error_code": "validation_failed", "errors": { "email": ["..."] } }
```

`error_code` стабилен и предназначен для ветвления на клиенте: `validation_failed`,
`unauthenticated`, `forbidden`, `not_found`, `too_many_requests`, `business_rule_violation`
и коды доменных исключений, которые вы добавите.

## Маршруты

Версия задаётся префиксом URL (`/api/v1`), в пространствах имён её нет — см. раздел
«Версионирование API» в [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md). Группы уже заведены
в `routes/api.php`: публичная и под `auth:sanctum`.

## SPA

Фронтенд (`orenza-frontend`) — отдельный origin: Vite слушает `http://localhost:3000`,
API отвечает на `http://localhost:8080`. Что связывает их:

- `FRONTEND_URL` в `.env` — список разрешённых origin'ов через запятую; из него
  `config/cors.php` собирает `allowed_origins` для `api/*`. Куки не используются
  (`supports_credentials => false`), SPA ходит с Bearer-токеном Sanctum;
- `GET /api/v1/ping` (`HealthController`) — публичная проверка связи: имя стенда,
  окружение, версия API и время. Клиент дёргает её на старте, чтобы отличить
  «сервер недоступен» от «вы не вошли»;
- на стороне SPA адрес лежит в `VITE_API_URL` (см. `orenza-frontend/.env.example`).

```bash
curl -H 'Origin: http://localhost:3000' http://localhost:8080/api/v1/ping
```

## Тесты

16 тестов (PHPUnit 12) на каркас: конверт ответов, доменные исключения, формат ошибок
валидации, `BaseData`/`Optional`, трейт перечислений.

```bash
make test      # SQLite in-memory — быстрый прогон
make test-pg   # PostgreSQL (база orenza_test) — паритет с production
```

Тестовая конфигурация зафиксирована в `phpunit.xml` через `<env force>` и `<server>`,
поэтому прогон никогда не попадёт в базу разработки — подробности в [docs/DOCKER.md](docs/DOCKER.md).
