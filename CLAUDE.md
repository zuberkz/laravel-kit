# laravel-kit — the studio kit

A Composer package shared by every Laravel site of the studio (`zuberkz/laravel-kit`, public on
GitHub). Code comments, docblocks and user-facing strings (`__()`) are in Russian; agent-facing
files — this one, `resources/rules/` — are in English. The kit follows the same studio rules it
ships to projects: read `resources/rules/studio.md` and the rule of the layer you touch.

**The repository is public**: no client names, no admin paths, no secrets, no server addresses —
not in code, tests, docs or commit messages.

## Layout

```
src/
  KitServiceProvider.php   kit.* config, the domain log channel, kit views, vendor:publish
                           tags, and the list of module providers — nothing else
  Core/                    used by two or more modules: exceptions, Support classes, shared
                           Filament fields, panel access
  Modules/<Module>/        one feature for projects: Access, Leads, Pages, Security, Seo, Spam,
                           Users
config/kit.php             defaults of every kit.* key
database/migrations/       migrations of the modules (published by the module's tag)
resources/rules/           rules shipped to projects (.claude/rules/kit/)
resources/stubs/           files shipped to projects (KitArchTest.php, pint.json)
resources/views/           views of the kit, namespace kit::
routes/<module>.php        routes of a module
tests/Feature/Modules/<Module>, tests/Feature/Core, tests/Unit/Core, tests/Fixtures
```

## A module

- Lives in `src/Modules/<Module>`, namespace `Zuber\Kit\Modules\<Module>`, with the studio layers
  inside: `Actions`, `Data`, `Enums`, `Exceptions`, `Models`, `Repositories`, `Services`,
  `Support`, `Http/Controllers`, `Http/Middleware`, `Livewire`, `Filament/Resources/<Resource>`
  (`Pages`, `Schemas`, `Tables` next to the resource). Suffixes and `final readonly` as in the
  studio rules; Larastan level 9 without ignores.
- Anything the module registers in the application — routes, middleware, commands, migrations,
  Livewire components, Blade namespaces, container tags — goes into its own
  `<Module>ServiceProvider`, listed in `KitServiceProvider::MODULES`. `KitServiceProvider` never
  learns details of a module.
- A Filament part is a plugin `<Module>Plugin` at the module root (`make()`, `current()`,
  `managePermission()`, `navigationGroup()`), resources authorise through
  `Core\Filament\Support\PanelAccessSupport`, never through model policies the project may not have.
- Dependencies: a module uses `Core` freely; another module only one way and on purpose (Pages →
  Seo for the sitemap, Leads → Spam for the honeypot); never a cycle. Something two modules need
  moves to `Core`.
- Optional packages (Filament, spatie/permission, Livewire) stay in `suggest` + `require-dev` with a
  `conflict` floor; a module that needs one checks `class_exists()` before registering.

## Configuration, schema, views

- Every setting is a key `kit.<module>.*` with a default and a Russian comment in
  `config/kit.php`; no `env()` there (Larastan) — projects pass env values in their own
  `config/kit.php`. A module that adds tables or routes is off until the project sets
  `kit.<module>.enabled`.
- Migrations: `database/migrations` with a fixed date in the name, published by the module's tag
  `kit-<module>-migrations` (`publishesMigrations`). Until a module is released to a production
  site, change its original migration; after that — only new migration files.
- Views: `resources/views/<module>/…` as `kit::<module>.…`, anonymous components in
  `resources/views/components` (`<x-kit::…>`), class components through the module's
  `Blade::componentNamespace`. Templates use the project's theme tokens (`bg-surface`, `text-ink`),
  never ad-hoc colours; projects add `@source` for kit views.
- Routes: `routes/<module>.php`, loaded by the module provider unless routes are cached; the only
  catch-all is `Route::fallback()`.

## Rules for projects

A module that projects use has a rule in `resources/rules/<topic>.md` (English, `paths`
frontmatter): how a site uses it, what not to build itself. A new module adds a row to the
README table; an arch rule a project must follow goes into `resources/stubs/tests/Arch/KitArchTest.php`.

## Tests and checks

- Tests mirror the code: `tests/Feature/Modules/<Module>`; test fixtures (a User, enums, a panel,
  blocks, a contact form) in `tests/Fixtures`. Every action with its refusals, every admin
  screen that writes, every access rule.
- Checks run in Docker, not on the host, on the studio's shared PHP image
  (`ghcr.io/zuberkz/laravel-php:8.5`, repository `../laravel-docker`):
  `docker compose run --rm php composer check` (Pint with `resources/stubs/pint.json`, Larastan,
  Pest). The GitHub workflow `checks.yml` runs the same.
- After moving classes or views, clear compiled views of testbench
  (`vendor/orchestra/testbench-core/laravel/storage/framework/views`).

## Releasing

- Commits as in the studio rules (Conventional Commits, Russian); every commit passes
  `composer check` on its own.
- Versions are tags `vX.Y.Z`. Before 1.0 a breaking change (namespaces, config keys, schema, an
  arch rule that turns sites red) bumps the minor version, after 1.0 the major. A tag that is on
  GitHub is never moved or rewritten; an unpushed one may be.
- The user pushes (PhpStorm, "Push tags: All"); nothing is released without tags.
- To try a change in a site before the release, the site uses the kit from `../laravel-kit` (a path
  repository with `symlink: true` and a `docker-compose.override.yml` mount), never edits
  `vendor/zuberkz/laravel-kit` — and switches back to the GitHub version before committing.
