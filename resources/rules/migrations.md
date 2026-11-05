---
paths:
  - "database/migrations/**/*.php"
  - "app/Repositories/**/*.php"
---

# Migrations and indexes

## Files and the deploy
- Until the first production release there is **one migration per table**: a new column goes into
  the original `create_*` migration, no `add_*_to_*_table`; environments are rebuilt with
  `migrate:fresh --seed`. When a migration that already ran on a staging server is edited, say so
  in the report: staging needs `migrate:fresh --seed`, a plain `migrate` will not apply it.
- After the first production release — new files only (`php artisan make:migration`): an edit to
  an old migration never reaches a database that already ran it.
- The deploy runs `migrate --force`: a migration must work on a non-empty table (defaults or
  nullable for new columns, no data loss without an explicit request).
- Production runs MariaDB/MySQL, tests run SQLite in memory: no engine-specific SQL in migrations.
  A project that needs engine features (full-text search, partial indexes, `jsonb`) moves to
  PostgreSQL everywhere, the test suite included (rule `testing.md`), and uses them directly.

## Columns
- Time — `timestamps()` / `timestamp()` / `dateTime()`, never the `*Tz()` variants: consistency
  comes from `APP_TIMEZONE` and the database time zone. A date without time (access until) is
  `date()`.
- Money — `decimal('amount', 14, 2)`; a controlled vocabulary — `string` + an enum cast
  (rule `enums.md`); a two-value flag — `boolean()`.
- Heavy content (an article body, an imported file's text) lives in its own table or column that
  list queries never select: a list page must not drag megabytes of text.
- Foreign keys — `foreignId()->constrained()` with an explicit `cascadeOnDelete()` /
  `nullOnDelete()` / `restrictOnDelete()`. Choose by the business rule, not by habit: what the
  user made (notes, bookmarks, progress) survives the end of their access, so it never cascades
  from an access, a membership or a code.

## Indexes — derived from the queries
Queries live only in repositories (rule `repositories.md`), so every index answers a repository
method. **A repository method and the index it needs go in the same commit**; a migration does not
get an index "just in case".

For every query look at `WHERE` (equality and ranges), `JOIN`, `ORDER BY` and how many rows the
table will hold, then:

- **Foreign keys.** MariaDB/MySQL (InnoDB) create an index for a `constrained()` column
  themselves; PostgreSQL does not — there every foreign key gets `$table->index('…')`, otherwise
  every join, `with()` and cascading delete scans the child table. The exception is a column that
  already leads a composite or unique index.
- **Composite index when a query filters by several columns together.** Column order:
  1. equality conditions first (`organization_id = ?`, `user_id = ?`),
  2. then the range or the sort (`created_at`, `priority`, `id`).
  An index `(a, b, c)` serves queries on `a`, `a + b`, `a + b + c` (the leftmost prefix), never
  `b` alone. So a separate index on `a` next to `(a, b)` is redundant — do not create both.
- **Sorted lists**: the index ends with the sort columns in the query's order, the tie-breaker
  `id` included — `orderBy('priority', 'desc')->orderBy('id')` inside a category is
  `index(['category_id', 'priority', 'id'])`.
- **Uniqueness is a constraint, not only validation**: whatever must not repeat (an email in a
  table, a product in a collection, a member of an organisation, an invite code) is a `unique()`
  index — two parallel requests both pass validation. A unique index is an index too: do not add
  a plain one on the same columns.
- **Low-selectivity columns** (`boolean`, a status with a few values) are never indexed alone. They
  go after a selective column in a composite index; on PostgreSQL, when the query always asks for
  one rare value (`unused` codes, `active` sessions), into a partial index
  (`… WHERE used_at IS NULL`).
- **Unique among live rows** (soft deletes, "one active device per slot"): on PostgreSQL a partial
  unique index `… WHERE deleted_at IS NULL`; on MariaDB/MySQL there are no partial indexes — a
  generated column that is `NULL` for deleted rows, unique together with the key.
- **Search in text**:
  - `LIKE 'abc%'` — a plain B-tree index works;
  - `LIKE '%abc%'` (name, title, SKU while typing) — no B-tree index helps: on PostgreSQL a GIN
    trigram index (`pg_trgm`, `CREATE EXTENSION IF NOT EXISTS pg_trgm` in its own migration), on
    MariaDB/MySQL a `fullText()` index with `whereFullText()` or a search engine (Meilisearch via
    Scout) for large tables;
  - full-text over long texts — `fullText()` on MariaDB/MySQL, a `tsvector` column with a GIN
    index on PostgreSQL. Never `LIKE '%…%'` over long texts.
- **JSON** filtered on often — a single key moves to its own (or a generated) column with an index;
  on PostgreSQL a `jsonb()` column queried by containment (`@>`) gets a GIN index (plain `json`
  cannot be indexed there).
- `morphs()` / `nullableMorphs()` already create the composite index on type + id.
- Do not index what no query uses: every index slows down writes, and tables written on every
  user action (progress, last activity) carry only the indexes their reads need.

## Checking an index
- For a list that will grow (members of an organisation, orders, search), run the repository
  query with `EXPLAIN` on realistic data (`database-query` of Boost, read-only) and look for a full
  table scan on a large table; fix the index, not the query shape.
- After the first production release a new index on a large table is built without locking
  writes: on PostgreSQL `public $withinTransaction = false;` in the migration and
  `CREATE INDEX CONCURRENTLY IF NOT EXISTS` in raw SQL (`CONCURRENTLY` cannot run inside the
  migration transaction); on MariaDB/MySQL `ALGORITHM=INPLACE, LOCK=NONE`.
- Index names are left to Laravel unless raw SQL creates them; raw names follow its pattern
  (`<table>_<columns>_index`), so `dropIndex()` finds them.

```php
Schema::create('notes', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('article_id')->constrained()->restrictOnDelete();
    $table->unsignedInteger('position');
    $table->text('quote');
    $table->text('comment')->nullable();
    $table->timestamps();

    // NoteRepository::forReader(): notes of a user in an article in reading order.
    // Leads with user_id, so user_id needs no index of its own.
    $table->index(['user_id', 'article_id', 'position', 'id']);
});
```
