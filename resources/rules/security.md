---
paths:
  - "app/Http/**/*.php"
  - "bootstrap/app.php"
  - "routes/web.php"
  - "resources/views/livewire/**"
---

# Security and spam protection

## Public forms
- **Every public form is protected** (contact, order, brief, comment, registration): the kit's
  `Zuber\Kit\Modules\Spam\HoneypotField::make()` in the form schema and the trait
  `Zuber\Kit\Modules\Spam\UsesSpamProtection` in the component (`if ($this->isSpam()) { …thank you…;
  return; }` before the action; minimum fill time — `kit.spam.min_seconds`), plus a rate limit — `throttle:5,1` on the route or `RateLimiter` in the action keyed
  by IP. A bot that fills the honeypot gets the normal "thank you" and nothing is stored, so it
  learns nothing.
- Login, password reset and registration are rate limited per email and per IP.
- Uploaded files are validated by MIME type and size, stored under generated names outside
  `public/` unless they are meant to be public, and never executed.

## Headers and transport
- Security headers come from the kit on every response (a global middleware):
  `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
  `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` that disables camera,
  microphone, geolocation, payment; `Strict-Transport-Security` in production over https. A site
  that needs one of them (a map with geolocation) changes `security_headers` in `config/kit.php`;
  never a second middleware with the same headers. A `Content-Security-Policy` once the list of third-party scripts (analytics,
  maps, widgets) is known — start in `Report-Only`.
- HTTPS only: `URL::forceHttps()` in production, secure session cookies (`SESSION_SECURE_COOKIE`).

## Output and input
- HTML from the admin panel or an import is rendered only through a sanitizer
  (`symfony/html-sanitizer` with an allow-list), never raw `{!! !!}`.
- Mass assignment only through `$fillable`; ids from the client are `#[Locked]` in Livewire and
  re-authorized before every write (rule `access.md`).
- No secrets in logs, exceptions, responses or Telegram messages; `APP_DEBUG=false` in production.
- Signed URLs (`URL::temporarySignedRoute`) for links in mail (unsubscribe, confirmations,
  downloads).

## Tests
A spam submission (honeypot filled, too fast) stores nothing and answers like a success; the
rate limit returns 429 after the limit; security headers are present on a public page.
