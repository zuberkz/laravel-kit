---
paths:
  - "app/Notifications/**/*.php"
  - "app/Mail/**/*.php"
  - "resources/views/mail/**"
---

# Mail and notifications

- Mail, Telegram and other notifications are Laravel notifications (`app/Notifications`) or
  mailables (`app/Mail`) — **always queued** (`ShouldQueue`) and `afterCommit()`, so a slow SMTP or
  a Telegram outage never breaks the request and a rolled-back transaction sends nothing.
- Who gets notified and when is decided by a service; the action calls it after the transaction
  (rule `actions.md`). A notification class only formats the message.
- Mail templates are Markdown mailables (`resources/views/mail`) with the site's theme; subject
  and texts through `__()`; one clear action button; a plain-text part.
- Telegram goes through a `TelegramClient` (rule `clients.md`) in a notification channel; the
  message is short, has a link to the record in the admin panel and never contains secrets or
  personal data beyond what the recipient needs.
- Leads and orders reach the team by two channels (mail + Telegram): one failing must not lose
  the lead — the record is stored first, notifications follow.
- Addresses and chat ids are configuration (`.env` → `config/services.php`), not code.
- Links in mail are absolute on `APP_URL`; actions from mail (unsubscribe, confirm) use signed
  URLs.
- Tests: `Notification::fake()` / `Mail::fake()` and `assertSentTo` with the recipient and the key
  content; a test that a failing channel does not prevent storing the record.
