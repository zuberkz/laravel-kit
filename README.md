# laravel-kit

Общий набор студии Zuber для Laravel-проектов. Правится в одном месте, в проекты приходит через
`composer update`:

| Часть | Где в пакете | Как попадает в проект |
|---|---|---|
| Правила для агентов (`studio.md` — общая часть CLAUDE.md, слои, исключения, тесты…) | `resources/rules` | `vendor:publish --tag=kit-rules` → `.claude/rules/kit/` |
| Arch-тесты студии | `resources/stubs/tests/Arch/KitArchTest.php` | `--tag=kit-arch` → `tests/Arch/KitArchTest.php` |
| Конфиг Pint | `resources/stubs/pint.json` | `--tag=kit-pint` → `pint.json` |
| `DomainException` и его обработка, канал лога `domain` | `src/Core`, `config` | автозагрузка и сервис-провайдер пакета |
| Разделы «Пользователи» и «Роли» в Filament | `src/Modules/Users` | плагин `UsersPlugin` в панели проекта |
| Первый администратор на сервере | `src/Modules/Users` | `php artisan kit:make-user почта --role=admin` — пароль печатается один раз; `--ask-password` — ввести свой (скрыто) |
| Права из `PermissionEnum`, роли по умолчанию из `RoleEnum` | `src/Modules/Access` | команда `kit:sync-roles`, её запускает общий деплой |
| Заголовки безопасности, `noindex` вне прода | `src/Modules/Security`, `src/Modules/Seo` | глобальные middleware, сами |
| `robots.txt`, `sitemap.xml`, `canonical_url()` | `src/Modules/Seo` | маршруты кита; разделы sitemap — классы проекта с тегом |
| Защита форм от спама | `src/Modules/Spam` | `HoneypotField` в схеме + трейт `UsesSpamProtection` |
| Поля `PhoneInput` (+7) и `SearchInput` | `src/Core/Filament/Forms/Fields` | в схемах Filament |
| Конструктор страниц: блоки, общие блоки, шапка и подвал, черновики | `src/Modules/Pages` | `kit.pages.enabled`, `Route::kitPages()`, `PagesPlugin`, миграции `--tag=kit-pages-migrations` |
| MCP конструктора страниц: ИИ-ассистент собирает и правит лендинги из блоков | `src/Modules/Pages/Mcp` | `PagesServer` в `routes/ai.php` проекта с OAuth и правом на страницы (пакет `laravel/mcp`) |
| Заявки: формы из конструктора в админке, заявки, письмо команде | `src/Modules/Leads` | `kit.leads.enabled`, `<livewire:kit-lead-form :form>`, `LeadFormSelect`, `LeadsPlugin`, миграции `--tag=kit-leads-migrations` |
| Деплой на сервер ISPmanager | `.github/workflows/deploy.yml` | вызывается из `.github/workflows/deploy.yml` проекта |

Опубликованные файлы перезаписываются при каждом `composer update`: правьте их здесь, а не в
проекте. Своё у проекта — `CLAUDE.md`, правила вне `.claude/rules/kit/`, `tests/Arch/ProjectArchTest.php`.

## Устройство

`src/Core` — общее для всех модулей (исключения, телефон, поля Filament, доступ к панели),
`src/Modules/<Модуль>` — модули со своими сервис-провайдерами; `KitServiceProvider` подключает
модули, настройки `kit.*` и публикацию файлов.

## Подключение

```bash
composer config repositories.laravel-kit vcs https://github.com/zuberkz/laravel-kit
composer require zuberkz/laravel-kit
```

Проект сам выбирает, что из набора забирать, — теги в `composer.json`:

```json
"post-update-cmd": [
    "@php artisan vendor:publish --tag=laravel-assets --ansi --force",
    "@php artisan vendor:publish --tag=kit-rules --tag=kit-arch --tag=kit-pint --ansi --force"
]
```

`bootstrap/app.php`:

```php
->withExceptions(new \Zuber\Kit\Core\Setup\ExceptionsSetup)
```

Отказы по бизнес-правилам наследуют `Zuber\Kit\Core\Exceptions\DomainException`.

## Пользователи и роли (Filament)

Нужны `filament/filament` ^5 и `spatie/laravel-permission` в проекте — кит их не ставит.

- «Пользователи»: список с ролями и поиском, фильтр по роли; форма — профиль, пароль (кнопка
  «Сгенерировать» даёт пароль вида `Kp7m-Xw3q-Rt9z` и сразу показывает его, рядом — копирование),
  роли галочками с описаниями. Записывают действия `CreateUserAction` / `UpdateUserAction`.
- Супер-администратор (разработчик) виден и выдаётся только супер-администраторам; себя удалить
  нельзя.
- «Роли»: роли заводят, переименовывают и удаляют в админке, права ролям отмечают галочками.
  Список прав — `PermissionEnum` проекта (право работает, только если его проверяет код), подписи
  и описания — `HasLabel` / `HasDescription`. Роли из `RoleEnum` создаёт `kit:sync-roles` с
  правами по умолчанию (`RoleEnum::permissions()`); дальше их права правят в админке, деплой их
  не трогает, переименовать или удалить такую роль нельзя. Супер-администратору синхронизация
  всегда выдаёт все права, в админке он не настраивается. Роль с пользователями не удаляется;
  права, которых нет у самого пользователя, он не выдаёт и не забирает. Записывают действия
  `CreateRoleAction` / `UpdateRoleAction` / `DeleteRoleAction`.

Модель `User` проекта: трейт `Spatie\Permission\Traits\HasRoles` и интерфейс
`Zuber\Kit\Modules\Users\Contracts\HasRolesInterface`. Панель:

```php
->plugin(
    UsersPlugin::make(RoleEnum::class)                    // enum: HasLabel, HasDescription — по желанию
        ->superAdminRole(RoleEnum::SUPER_ADMIN)           // по умолчанию kit.access.super_admin
        ->managePermission(PermissionEnum::MANAGE_USERS)  // без него раздел открыт всем в панели
        ->permissions(PermissionEnum::class)              // галочки прав; по умолчанию kit.access.permissions
        ->navigationGroup(NavigationGroupEnum::ACCESS),
)
```

## Деплой

`.github/workflows/deploy.yml` проекта — только вызов общего workflow с переменными и секретами
репозитория (их создаёт `project-bootstrap`):

```yaml
name: "🚀 Deploy"

on:
    push:
        branches:
            - main
        # Пуш только с документацией, правилами ИИ-агентов и локальным окружением деплой не запускает
        paths-ignore:
            - '**.md'
            - '.ai/**'
            - '.agents/**'
            - '.claude/**'
            - '.mcp.json'
            - 'boost.json'
            - '.docker/**'
            - 'docker-compose.yml'
            - 'bin/**'
            - '.editorconfig'
            - '.github/dependabot.yml'
    workflow_dispatch:

# Параллельные деплои затирают друг друга: ждём предыдущий, новый не отменяем.
concurrency:
    group: deploy-production
    cancel-in-progress: false

# contents: write — закоммитить composer.lock, если его ещё нет в репозитории
permissions:
    contents: write

jobs:
    deploy:
        uses: zuberkz/laravel-kit/.github/workflows/deploy.yml@main
        with:
            app-dir: ${{ vars.APP_DIR }}
            php: ${{ vars.PHP }}
            deploy-branch: ${{ vars.DEPLOY_BRANCH }}
            auto-migrate: ${{ vars.AUTO_MIGRATE }}
            deploy-env: ${{ vars.DEPLOY_ENV }}
            rsync-exclude: ${{ vars.RSYNC_EXCLUDE }}
            php-version: '8.5'
            # sqlite (по умолчанию) | mariadb | pgsql — на чём идут тесты
            database: sqlite
            # команды перед тестами, если проекту они нужны
            # test-setup: php artisan passport:keys
        secrets:
            SSH_HOST: ${{ secrets.SSH_HOST }}
            SSH_USER: ${{ secrets.SSH_USER }}
            SSH_PORT: ${{ secrets.SSH_PORT }}
            DEPLOY_KEY: ${{ secrets.DEPLOY_KEY }}
```

Сам общий workflow короткий, шаги — composite actions в `.github/actions`: `build` (PHP, composer и npm,
сборка фронта), `checks` (audit, Pint, Larastan, тесты), `upload` (composer без dev и rsync), `finish`
(artisan на сервере). Всё идёт одним job: каждый job заново поднимает машину и округляет минуты.

Зависимости кэшируются: `vendor` — по `composer.lock` и версии PHP, `node_modules` — по
`package-lock.json` и версии Node. Пока lock-файлы не менялись, пакеты не скачиваются заново.

Проекты берут workflow с `@main`, поэтому правка деплоя доходит до всех сразу. Значит, изменения
в `deploy.yml` только совместимые; новый обязательный вход или другое поведение — новый файл
`deploy-v2.yml`, проекты переходят на него по одному.

## Версии

Semver по тегам `vX.Y.Z`. Новое arch-правило или правило Pint может сделать проект красным, а
красная проверка останавливает деплой, — такие изменения выходят в новой major-версии, и проект
переходит на неё сам, исправив код.

## Разработка

Как устроены модули, куда класть код, конфиг, миграции, шаблоны и тесты, как выпускать версии —
в [CLAUDE.md](CLAUDE.md).


```bash
docker compose build
docker compose run --rm php composer install
docker compose run --rm php composer check   # pint, larastan, pest
```

Проверить изменение на проекте до релиза — подключить пакет из соседней папки, не коммитя это в
проекте: `composer config repositories.laravel-kit path ../laravel-kit` (в Docker папка пакета
должна быть смонтирована в контейнер), затем вернуть `vcs`.
