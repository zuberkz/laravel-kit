# laravel-kit

Общий набор студии Zuber для Laravel-проектов. Правится в одном месте, в проекты приходит через
`composer update`:

| Часть | Где в пакете | Как попадает в проект |
|---|---|---|
| Правила для агентов (`studio.md` — общая часть CLAUDE.md, слои, исключения, тесты…) | `resources/rules` | `vendor:publish --tag=kit-rules` → `.claude/rules/kit/` |
| Arch-тесты студии | `resources/stubs/tests/Arch/KitArchTest.php` | `--tag=kit-arch` → `tests/Arch/KitArchTest.php` |
| Конфиг Pint | `resources/stubs/pint.json` | `--tag=kit-pint` → `pint.json` |
| `DomainException` и его обработка, канал лога `domain` | `src`, `config` | автозагрузка и сервис-провайдер пакета |
| Разделы «Пользователи» и «Роли» в Filament | `src/Users` | плагин `UsersPlugin` в панели проекта |
| Синхронизация ролей и прав с `RoleEnum` | `src/Access` | команда `kit:sync-roles`, её запускает общий деплой |
| Заголовки безопасности, `noindex` вне прода | `src/Http`, `src/Seo/Http` | глобальные middleware, сами |
| Поля `PhoneInput` (+7) и `SearchInput` | `src/Filament/Forms/Fields` | в схемах Filament |
| Деплой на сервер ISPmanager | `.github/workflows/deploy.yml` | вызывается из `.github/workflows/deploy.yml` проекта |

Опубликованные файлы перезаписываются при каждом `composer update`: правьте их здесь, а не в
проекте. Своё у проекта — `CLAUDE.md`, правила вне `.claude/rules/kit/`, `tests/Arch/ProjectArchTest.php`.

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
->withExceptions(new \Zuber\Kit\Setup\ExceptionsSetup)
```

Отказы по бизнес-правилам наследуют `Zuber\Kit\Exceptions\DomainException`.

## Пользователи и роли (Filament)

Нужны `filament/filament` ^5 и `spatie/laravel-permission` в проекте — кит их не ставит.

- «Пользователи»: список с ролями и поиском, фильтр по роли; форма — профиль, пароль (кнопка
  «Сгенерировать» даёт пароль вида `Kp7m-Xw3q-Rt9z` и сразу показывает его, рядом — копирование),
  роли галочками с описаниями. Записывают действия `CreateUserAction` / `UpdateUserAction`.
- Супер-администратор (разработчик) виден и выдаётся только супер-администраторам; себя удалить
  нельзя.
- «Роли» — только просмотр: роли с правами и числом пользователей. Роли и права задаются в коде
  (`RoleEnum::permissions()`) и раскладываются сидером.

Модель `User` проекта: трейт `Spatie\Permission\Traits\HasRoles` и интерфейс
`Zuber\Kit\Users\Contracts\HasRolesInterface`. Панель:

```php
->plugin(
    UsersPlugin::make(RoleEnum::class)                    // enum: HasLabel, HasDescription — по желанию
        ->superAdminRole(RoleEnum::SUPER_ADMIN)
        ->managePermission(PermissionEnum::MANAGE_USERS)  // без него раздел открыт всем в панели
        ->permissions(PermissionEnum::class)              // подписи прав в разделе «Роли»
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
        secrets:
            SSH_HOST: ${{ secrets.SSH_HOST }}
            SSH_USER: ${{ secrets.SSH_USER }}
            SSH_PORT: ${{ secrets.SSH_PORT }}
            DEPLOY_KEY: ${{ secrets.DEPLOY_KEY }}
```

Проекты берут workflow с `@main`, поэтому правка деплоя доходит до всех сразу. Значит, изменения
в `deploy.yml` только совместимые; новый обязательный вход или другое поведение — новый файл
`deploy-v2.yml`, проекты переходят на него по одному.

## Версии

Semver по тегам `vX.Y.Z`. Новое arch-правило или правило Pint может сделать проект красным, а
красная проверка останавливает деплой, — такие изменения выходят в новой major-версии, и проект
переходит на неё сам, исправив код.

## Разработка

```bash
docker compose build
docker compose run --rm php composer install
docker compose run --rm php composer check   # pint, larastan, pest
```

Проверить изменение на проекте до релиза — подключить пакет из соседней папки, не коммитя это в
проекте: `composer config repositories.laravel-kit path ../laravel-kit` (в Docker папка пакета
должна быть смонтирована в контейнер), затем вернуть `vcs`.
