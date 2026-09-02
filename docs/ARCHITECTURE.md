# Архитектура

Сейчас в проекте только каркас: доменного кода нет, есть базовые классы, конвенции и
генераторы под них. Ниже — как устроен поток запроса и что писать в каждом слое.

## Поток запроса

```
HTTP → Route → FormRequest (валидация) → Controller (авторизация)
                                            ↓
                                        Service (оркестрация use-case)
                                            ↓
                                        Action (одна бизнес-операция, транзакция, событие)
                                            ↓
                                        Repository (доступ к данным)
                                            ↓
                                        Model / DB
                                            ↑
                                     Resource + ApiResponse → JSON
```

Правило зависимостей: слой знает только о слое ниже. Модель не «поднимается» в контроллер
напрямую, Request/Response не спускаются ниже контроллера — вместо них ходят DTO.

Не каждый запрос обязан проходить все слои. Если операция тривиальна (один запрос к БД,
никаких инвариантов), контроллер вправе обратиться сразу к репозиторию — Action и Service
заводятся там, где есть что оркестрировать или переиспользовать.

## Версионирование API

Версия живёт **в URL** (`/api/v1/...`), а не в пространствах имён. Контроллеры лежат плоско
в `App\Http\Controllers\Api`, реквесты — в `App\Http\Requests\<Домен>`.

Почему так: префикс в URL даёт клиенту стабильный контракт и стоит одну строку в
`routes/api.php`. Каталог `Api/V1/...` в неймспейсах не даёт ничего, пока версия одна, —
это лишний уровень вложенности в каждом `use`. Когда появится вторая версия и первую
понадобится поддерживать параллельно, тогда и заводятся `Controllers\Api\V2` (или отдельный
`routes/api_v2.php`), а старые классы остаются на месте.

## Слои

### Enums (`app/Enums`)
Доменные состояния как типы, а не строки. Трейт `Concerns\InteractsWithEnum` даёт
`values()`, `names()`, `options()`, `tryFromName()`, `in()/notIn()`; контракт
`Contracts\HasLabel` — человекочитаемую подпись для API. В enum'е уместно держать и правила:
уровни доступа, допустимые переходы состояний, производные права.

```php
enum OrderStatus: string implements HasLabel
{
    use InteractsWithEnum;

    case New = 'new';
    case Paid = 'paid';

    public function label(): string { /* ... */ }

    /** @return array<int, self> */
    public function allowedTransitions(): array { /* ... */ }
}
```

### DTO (`app/DataTransferObjects`)
Неизменяемые объекты переноса данных. `BaseData::toArray()` приводит свойства к snake_case
и **выбрасывает непереданные поля**: их значение — сентинел `App\Support\Optional`. Это
отличает «не передали» от «передали null», благодаря чему `PUT/PATCH` обновляет ровно те
поля, которые пришли, без ручных `array_filter`. Наследник обязан реализовать `fromArray()`.

### Repositories (`app/Repositories`)
Контракты в `Contracts`, реализации в `Eloquent`. `BaseRepository` закрывает CRUD, пагинацию
и `applyCriteria()` (массив → `where`/`whereIn`/`whereNull`); конкретный репозиторий добавляет
запросы своей предметной области. Биндинги — единой картой в `RepositoryServiceProvider`,
поэтому замена реализации (кеш-декоратор, внешний API, in-memory fake в тестах) — правка
одной строки.

### Actions (`app/Actions`)
Одна операция — один класс с единственным методом `handle()`. Действие само открывает
транзакцию, проверяет инварианты и бросает `DomainException`, а по завершении диспатчит
доменное событие. Действия переиспользуются из HTTP, консоли, очередей и других действий —
именно поэтому бизнес-правила живут здесь, а не в контроллере или сервисе.

### Services (`app/Services`)
Фасад use-case'ов: собирает действия и репозитории в сценарий. Собственных бизнес-правил не
содержит — иначе они станут недоступны вне этого сценария.

### HTTP (`app/Http`)
- `ApiFormRequest` — общий JSON-конверт ошибок валидации; наследники объявляют `rules()`
  и метод `toData()`, возвращающий DTO.
- Контроллеры тонкие: авторизовать (`$this->authorize()` + политика), делегировать сервису,
  отдать ресурс.
- `ApiResponse` — единственное место, где решается форма ответа
  (`data` / `message` / `meta` / `error_code`), включая `paginated()` для списков.
- `ForceJsonResponse` проставляет `Accept: application/json` всем запросам к `api/*`.

### Ошибки (`app/Exceptions`)
`DomainException` — база для ожидаемых бизнес-сбоев: несёт HTTP-статус, стабильный
`error_code`, контекст и сама рендерится в JSON. Поэтому контроллеры обходятся без
`try/catch`. Наследники объявляют `$status` и `$errorCode` и добавляют именованные
конструкторы. Исключения фреймворка (`AuthenticationException`, `AccessDeniedHttpException`,
`NotFoundHttpException`, `TooManyRequestsHttpException`) приведены к тому же конверту
в `bootstrap/app.php`.

## Договорённости

- `declare(strict_types=1)` во всех файлах приложения; Pint (`pint.json`) следит за стилем.
- Классы реализаций — `final`, зависимости — через конструктор, состояние — `readonly`.
- Модели в non-production работают в строгом режиме (`Model::shouldBeStrict()`): ленивая
  загрузка и обращение к отсутствующим атрибутам падают сразу, а не в проде.
- Даты — `CarbonImmutable`.
- Лимиты: `throttle:api` (60/мин) и `throttle:auth` (5/мин на пару e-mail+IP) — второй
  вешается на маршруты входа и регистрации, когда они появятся.

## Как добавить новую сущность

Команды выполняются внутри контейнера: `make artisan c="make:model Post -m"`
(или `make shell` и дальше обычный `php artisan`).

1. `make:model Post -m` — модель и миграция.
2. `make:enum PostStatus --string` — доменные состояния; подключите `InteractsWithEnum`
   и `HasLabel`.
3. `make:repository Post --model=Post` — контракт + Eloquent-реализация; зарегистрируйте
   пару в `RepositoryServiceProvider::$bindings`.
4. `make:data Posts/PostData` — DTO и `fromArray()`.
5. `make:action Posts/CreatePostAction` — бизнес-операция.
6. `make:service PostService` — сценарии (если операций больше одной).
7. `make:request Posts/StorePostRequest`, `make:resource PostResource`,
   `make:policy PostPolicy --model=Post`, `make:controller Api/PostController --api` —
   HTTP-слой; маршруты в `routes/api.php` внутри группы `v1`.
8. Тесты: feature на эндпоинты, unit — на enum'ы, DTO и нетривиальные действия.
