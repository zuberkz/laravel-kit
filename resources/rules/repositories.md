---
paths:
  - "app/Repositories/**/*.php"
  - "app/Models/**/*.php"
---

# Repositories and models

## Repositories (`app/Repositories`)
- **Database queries live only in repositories**: not in models (no scopes either), controllers,
  Livewire components, services or actions. `DB::` is allowed only here. The arch tests check it.
- One repository per table: `OrderRepository` reads and writes `orders`; `order_items` has its own
  `OrderItemRepository`. A repository does not call another repository.
- No business rules: who sees what, which statuses count as finished, which files to delete —
  services and enums decide; the repository receives ready conditions
  (`listFor(User $user, bool $withArchive, OrderFilterData $filter)`).
- A new record comes from a DTO: `create(OrderData $data)` → `Order::query()->create([...$data->toArray(), …])`,
  keys are table columns (`#[MapOutputName(SnakeCaseMapper::class)]` on the DTO, rule `data.md`).
  The repository adds only the link (`order_id`) and service fields (`status`, `author_id`); it
  does not copy fields one by one, and arrays of attributes are never passed into it.
- Method names say what changes: `markPaid()`, `applyPendingEmail()`, `anonymizeAndDelete()` —
  never a generic `update(array $attributes)`.
- A repository knows nothing about file storage: disk, file type, size and deletion of a file
  belong to a storage service.
- `final readonly class`, the name ends with `Repository`.
- Lists ordered by `priority DESC, id`: the secondary `id` is mandatory, equal priorities
  otherwise come back in arbitrary order.
- Every query has an index that serves it; a new method and its index go in the same commit
  (rule `migrations.md`). Lists select the columns they show — never the heavy text columns
  (an article body, an imported file's text) — and are paginated: `paginate()` for numbered pages,
  `cursorPaginate()` for endless lists and large tables.
- Frequent writes of one fact (reading progress, last activity of a device) are one
  `upsert()` / `updateOrCreate()` by the unique key, not "find, then insert or update"; the
  client sends them debounced, not on every scroll.
- Cache through `Cache::remember` with keys from an enum; invalidate in a model observer on
  `saved`/`deleted`. Mass `query()->update()` fires no model events — avoid it for cached data.

## Models (`app/Models`)
- A model holds attributes, casts, relations and calculations over **its own** fields
  (`isOverdue()`, `displayNumber()`) — nothing else. No queries, no scopes, no services, no HTTP.
  Rules (which actions are available, the next step) live in services, access in policies, the
  readable name of a value in the enum's label.
- No translated captions in models; texts for a model live in the view or the Livewire component.
- Casts for every non-string column: enums, `boolean`, `datetime`, `decimal:2`, `array`, `*Data`
  for structured JSON (rule `data.md`). `$fillable` explicitly; relations typed (`BelongsTo`).
- Lazy loading is forbidden outside production (`Model::preventLazyLoading()` in
  `AppServiceProvider`): relations are loaded explicitly by the repository (`with`,
  `loadMissing`); a `hasMany` back to its parent uses `chaperone()`.
- `@property` docblocks on every model for Larastan; dates are `CarbonImmutable`
  (`Date::use(CarbonImmutable::class)` in `AppServiceProvider`).
