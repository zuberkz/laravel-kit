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
- Telegram is the kit's module (`Zuber\Kit\Modules\Telegram`), a site never writes its own bot
  client or channel: the notification implements `TelegramNotificationInterface`, returns
  `[TelegramChannel::class]` from `via()` and builds the message in `toTelegram()` as a
  `TelegramMessageData` (title, fields, text, link — escaped and cut to the Telegram limit by the
  kit). To the team chat (`kit.telegram`) — `TelegramService::notify($notification)`; to a
  model — its `routeNotificationForTelegram()` returning `['chat_id' => …, 'thread_id' => …]`. The
  message is short, has a link to the record in the admin panel and never contains secrets or
  personal data beyond what the recipient needs.
- Leads and orders reach the team by two channels (mail + Telegram): one failing must not lose
  the lead — the record is stored first, notifications follow.
- Addresses and chat ids are configuration (`.env` → `config/services.php`, Telegram → `kit.telegram`
  in the project's `config/kit.php`), not code. Setup: the client creates a bot in @BotFather and a
  group with Topics, makes the bot an admin with "Manage topics" and writes a message there; then
  `php artisan kit:telegram` lists the chat id, `--topic="Заявки с сайта"` creates the topic and
  prints its id, `--test` sends a sample message.
- Leads of the kit already go to mail and Telegram (`kit.leads.mail_to`, `kit.telegram`, rule
  `pages.md`): a site configures them, it does not write its own lead notifications.
- Links in mail are absolute on `APP_URL`; actions from mail (unsubscribe, confirm) use signed
  URLs.
- Tests: `Notification::fake()` / `Mail::fake()` and `assertSentTo` with the recipient and the key
  content; a test that a failing channel does not prevent storing the record.
