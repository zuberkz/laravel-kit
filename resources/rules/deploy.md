---
paths:
  - ".github/workflows/**"
  - "config/**/*.php"
  - ".env.example"
  - "public/.user.ini"
---

# Deploy and production

## The pipeline (GitHub Actions → ISPmanager server)
- The pipeline is the reusable workflow `zuberkz/laravel-kit/.github/workflows/deploy.yml@main`; the
  site's `.github/workflows/deploy.yml` only calls it with the repository variables and secrets.
  A fix to the pipeline goes into the kit and reaches every site at once; never copy the steps
  back into a site.
- Checks first, then the server: `composer audit` (a known vulnerability stops the deploy),
  Pint, Larastan, Pest; `npm audit --omit=dev --audit-level=high`, lint, build. Assets are built in
  Actions — there is no node on the server.
- `bootstrap/cache` is uploaded with `vendor`: `composer install --no-dev` in Actions builds the
  package manifest for exactly that `vendor`, and `rsync --delete` removes stale caches. Excluding
  it once left a manifest pointing at a removed package, and artisan could not start.
- On the server, in this order: `git reset --hard origin/main` → `composer install --no-dev
  --optimize-autoloader` → assets in place → `migrate --force` → `optimize:clear` → `optimize` →
  `queue:restart` → clear the response cache if the site uses it.
- **Caches are rebuilt on every deploy.** A stale `route:cache` after a package update serves 404
  for routes the new version added (Livewire's `livewire.min.js` after an update is the classic
  case); a stale `config:cache` ignores `.env` changes. After editing `.env` on the server —
  `config:clear` and `optimize`.
- Migrations run automatically; data operations do not — they are commands run by hand
  (rule `commands.md`), mentioned in the commit body or the task report.

## Configuration
- `.env.example` lists every key the code reads, with a safe default or an empty value and a
  comment; a new config key goes there in the same commit.
- `env()` only in `config/`; the code reads `config()`. Secrets only in `.env` on the server and
  in GitHub secrets, never in the repository or in variables.
- Per-environment choices are config, not `if (app()->isProduction())` in code: disks
  (`public` / `s3`), queue connection, mail driver, image driver.

## PHP and the server
- The PHP version is the same locally (Docker image), in CI (`setup-php`) and on the site
  (ISPmanager); its binary path in workflows and cron is a variable, not a literal
  (`/opt/php85/bin/php`).
- Extensions the code needs (`bcmath`, `intl`, `imagick`, `redis`, `pdo_mysql`/`pdo_pgsql`) are
  enabled for that PHP version in the panel before the first deploy.
- Upload limits for the site — `public/.user.ini` in the repository (`upload_max_filesize`,
  `post_max_size`), kept in sync with form limits (rule `media.md`).

## Monitoring
- `/up` (Laravel's health route) is checked by an external monitor every few minutes.
- New errors in `laravel.log` reach the team (a log channel to Telegram for `error` and above),
  refusals go to the `domain` channel (rule `exceptions.md`) and are reviewed, not alerted.
- After a deploy look at `failed_jobs` and the error log; logs rotate daily and are kept 14–30
  days.
