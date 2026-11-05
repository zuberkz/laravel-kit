---
paths:
  - "app/Jobs/**/*.php"
  - "app/Listeners/**/*.php"
  - "config/queue.php"
  - "routes/console.php"
---

# Queues and jobs

- Slow or external work goes to the queue: mail, notifications, external APIs, image
  conversions, imports. A request never waits for them.
- A job is thin: it takes ids or a `*Data`, resolves an action or a service and calls it. Business
  rules are not written in the job. Models are passed with `SerializesModels` (only the id is
  stored); a deleted model fails the job quietly with `$deleteWhenMissingModels = true`.
- **Every job declares its limits**: `$tries` (or `retryUntil()`), `$timeout`, `backoff()`.
  A job that waits for something (a rate limit, an external status) uses `backoff()` and
  `retryUntil()`; it never `release()`s itself in an endless loop — every `release()` increments
  `attempts`, a `TINYINT` column that overflows at 255 and breaks the worker.
- `retry_after` of the connection (`config/queue.php`) is **longer than the longest `$timeout`**
  of its jobs, otherwise a running job is handed to a second worker and counted as a failed
  attempt.
- A refusal that will never succeed (`DomainException`, a deleted record) is not retried:
  `$this->fail($e)` or `$deleteWhenMissingModels`. Real failures propagate — the queue retries
  them and records them in `failed_jobs` (rule `exceptions.md`).
- Duplicates are prevented by `ShouldBeUnique` (`uniqueId()`), overlapping by the
  `WithoutOverlapping` middleware; rate limits of an external API by `RateLimited`.
- Heavy jobs (image conversions, imports, PDF) run on their own queue (`media`, `import`) so they
  do not block mail; the worker listens to `--queue=default,media` in priority order.
- A job dispatched inside a transaction is `afterCommit()` — otherwise it may run before the data
  it needs exists.
- Production worker: supervisor, `queue:work --tries=… --timeout=… --max-time=3600` (restarts
  hourly, picks up new code), `queue:restart` after every deploy, and log rotation in the
  supervisor program (`stdout_logfile_maxbytes=10MB`, `stdout_logfile_backups=3`) — an unrotated
  worker log grows to hundreds of megabytes.
- Memory: a worker runs with the CLI `memory_limit`; image work uses the Imagick driver (its
  memory is outside PHP's limit) or a raised limit for that queue only.
- `failed_jobs` is read, not collected: after a deploy or an incident look at it, fix the cause,
  then `queue:retry` or `queue:flush`.
- Tests: `Queue::fake()` + `Queue::assertPushed(…)` for dispatching; the job itself is tested by
  calling `handle()` with real dependencies.
