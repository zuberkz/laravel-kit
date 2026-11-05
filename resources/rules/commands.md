---
paths:
  - "app/Console/**/*.php"
  - "routes/console.php"
  - "database/migrations/**/*.php"
---

# Console commands and one-off operations

- **A migration changes only the schema.** Data work — converting files, regenerating images,
  moving uploads to S3, cleaning orphan rows, recalculating values — is an artisan command, run
  by hand after the deploy. A migration that touches files or calls a service runs at an
  unpredictable moment, cannot be repeated, and its rollback is a lie.
- A command is thin like a controller: it parses arguments, calls an action or a service and
  prints the result (`$this->components->info("Переведено: {$count}.")`). The work is in the
  service, so it is testable and reusable.
- Commands are idempotent: a second run does only what is left ("скопировано 0, уже были 11185")
  and never duplicates or deletes twice.
- Destructive commands make a backup first (a JSON export of the rows to `storage/app/…`, a dump)
  and print where it is; they have a `--dry-run` that only reports.
- Long commands show progress (`$this->withProgressBar()`), process in chunks
  (`chunkById()`, lazy collections) and can be resumed.
- Name — `domain:verb` (`images:convert-text`, `uploads:move`), description in Russian, arguments
  with descriptions; the command class ends with `Command`.
- Scheduled tasks are declared in `routes/console.php` with `->withoutOverlapping()` and
  `->onOneServer()`; a heavy task dispatches a job instead of doing the work inside the
  scheduler.
- Tests: `$this->artisan('…')->expectsOutputToContain(…)->assertSuccessful()` plus the database or
  storage state.
