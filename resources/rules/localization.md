---
paths:
  - "lang/**"
  - "resources/views/**/*.blade.php"
  - "app/**/*.php"
---

# Localization

- Every user-facing string goes through `__()`, texts in Russian (`APP_LOCALE=ru`). Validation and
  framework messages come from `lang/ru/*` (package `laravel-lang/common`).
- A site in several languages keeps the source texts (the keys) in Russian with translations in
  `lang/<locale>.json`; the fallback stays `ru`, so a missing line shows the Russian text, never
  another language. The default language is its own config key (`app.default_locale`, read from
  `APP_LOCALE`), not `app.locale`: `app()->setLocale()` overwrites `app.locale` on every request.
  The admin panel language is fixed separately (its own config key and a middleware), tests pin
  one locale in `phpunit.xml`.
- Filament and other packages lack some locales (Kazakh among them): strings of package components
  used on the site live in `lang/vendor/<package>/<locale>`. A new package component on the site
  needs its lines there.
- **`trans_choice()` with a phrase instead of a translation key pluralises incorrectly**: the
  translator takes the current locale only when the key exists, otherwise the English "1 or not 1"
  rule gives «5 дня». Add a key to `lang/ru.json`: the key in English, the Russian forms separated
  by `|` — `"in :days days": "через :days день|через :days дня|через :days дней"`, called as
  `trans_choice('in :days days', $days, ['days' => $days])`. Check 1, 2, 5, 11, 21.
- `lang/ru.json` is maintained by `laravel-lang` — your own keys can be overwritten by an update;
  check after `composer update`.
- Attribute names in validation messages — `lang/ru/validation.php` → `attributes`.
