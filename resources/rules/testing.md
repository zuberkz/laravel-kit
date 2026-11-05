---
paths:
  - "tests/**/*.php"
---

# Tests (Pest)

- Every feature comes with Pest tests in the same commit: a feature test per page/endpoint
  (status, content, validation errors, database state), a unit test for logic without the
  framework.
- Mandatory coverage: access (every policy method allowed and refused, foreign records invisible
  in lists and unreachable by id), status transitions, and for every action — the success path
  and its main refusals.
- Log in with a helper that starts a fresh session like a real login (`signIn($user)` in
  `tests/Pest.php`), not by setting the guard by hand.
- Check links by route (`route('orders.show', $order)`), not by a visible number or text that
  can repeat elsewhere on the page.
- `tests/Arch/KitArchTest.php` holds the studio's architecture rules (presets php, security,
  laravel + the suffix and layer rules). It comes from `zuberkz/laravel-kit` and is overwritten on
  `composer update`: a new studio convention goes into the kit, a rule of one site into
  `tests/Arch/ProjectArchTest.php`. Never relax either.
- Test names and dataset keys are in English; comments stay Russian; expected values stay Russian
  when they are UI strings.
- Feature tests use `RefreshDatabase` (set in `tests/Pest.php`) on SQLite in memory
  (`phpunit.xml`). When the code uses engine features (full-text search, partial indexes, `jsonb`,
  raw SQL), the suite runs on the production engine instead — a test database in the Docker
  service and a database service in the deploy workflow — never SQLite: indexes, JSON and search
  behave differently there.
- Factories and their states instead of attributes by hand; check existing states first.
- No live external requests: `Http::fake()` / `Http::preventStrayRequests()`, `Mail::fake()`,
  `Queue::fake()`. Services and clients are `final` — Mockery cannot mock them: fake the boundary
  (`Http::fake()` under the real client), or, when a test really needs a stand-in, make the caller
  depend on an interface from `app/Contracts` and mock that (`mock(SmsSenderInterface::class)`).
- Datasets for input variations instead of copy-pasted tests.
- Helper functions in Pest files are global for the whole suite — unique names, otherwise a fatal
  on the full run.
- Compare arrays whose key order is not guaranteed with `toEqual`, not `toBe`.
- Filament: `Livewire::test(Page::class)`, test actions with `callAction()`, not
  `call('method')` — a direct call skips mounting, the test is green while the UI is broken. The
  admin panel gets feature tests only.

## Browser tests (when the site adds them)
- Pest 4 Browser in `tests/Browser` (testsuite `Browser`), run in a separate Docker service and a
  separate workflow, not in the deploy. Run `npm run build` before a local run.
- For every screen: a smoke check (`assertNoSmoke`) under every role, the main scenario by
  clicks, mobile and dark theme.
- Pin Playwright to the version the plugin was built with
  (`vendor/pestphp/pest-plugin-browser/package-lock.json`), the same in `package.json` and the
  Dockerfile: on a newer one clicks and typing hang without an error.
- A Filament field is addressed by attribute: `[id="form.email"]` (a dot in an id is otherwise read
  as a CSS class). Fields of modals share ids — scope them by the form: `'#order-form [id="form.status"]'`.
- The Pest test server does not accept file uploads: attach files to the model directly, test the
  upload in a feature test. Signed URLs are built from the test server address (the host is part
  of the signature). A modal animates in: `assertVisible(…)->wait(0.3)` before typing.
