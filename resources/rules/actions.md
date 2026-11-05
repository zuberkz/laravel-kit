---
paths:
  - "app/Actions/**/*.php"
  - "app/Http/Controllers/**/*.php"
  - "resources/views/pages/**"
  - "resources/views/livewire/**"
---

# Actions and entry points

## Layers, without skipping
- Reading: controller / Livewire component → service → repository → model.
- Writing: controller / Livewire component → action → service → repository → model.
- An entry point (controller, Livewire component, console command, Filament action) never builds
  queries (`Model::query()`, `$order->items()->…`, `load()`) and never writes to the database
  itself: validated input becomes a `*Data`, the `*Data` goes to an action. Relations the screen
  needs are loaded by the repository.
- Reading also goes through a service, not straight to a repository: the query lives in the
  repository, the rule (what this user may see) lives in the service. The entry point only adds
  search, filters and display labels.
- Seeders and factories are the exception: they fill the database directly.

## Actions (`app/Actions/<Domain>`)
- One action = one user scenario that changes data: `PlaceOrderAction`, `CancelOrderAction`.
  `final readonly class`, the name ends with `Action`, one public method — `handle()`.
- The action only decides which services to call and in which order. No business rules, no
  queries, no work with models beyond passing them on.
- A scenario that writes to several tables runs in a transaction, and **only the action opens
  it**: inject `Illuminate\Database\ConnectionInterface` and call `$this->db->transaction(fn () => …)`.
  Services and repositories never open transactions.
- Consequences for other subjects (a notification, a stock change, a log entry) are methods of
  their own services, each checking whether the event concerns it. The action calls them in
  order — the source service does not call them.
- Side effects that must not run twice (mail, external API calls) go after the transaction, or
  are queued with `afterCommit()`.
- **Limits and one-time things are checked where they are spent, atomically.** A seat limit, a
  one-time invite code, a device limit: "check, then write" in two queries lets two parallel
  requests both pass. The action locks the counted row inside the transaction
  (`lockForUpdate()` through the repository — the organisation row for its seats) or the
  repository spends it with one conditional update and the action checks the affected rows
  (`update … set used_at = now() where id = ? and used_at is null` → 0 rows means "already
  used"). A unique index is the last line of defence (rule `migrations.md`); its violation
  (`UniqueConstraintViolationException`) becomes the same refusal, not a 500.
- Called through the container only (constructor injection or a method parameter), never `new`.
- Not to be confused with Filament actions (`app/Filament/**/Actions`): those are UI — a button,
  a modal and authorization — and call an action from `app/Actions` for the work.

## Controllers
- Thin: a `*RequestData` parameter (validates itself), an action or a service, a response.
- The current user is a parameter: `#[CurrentUser] User $user`
  (`Illuminate\Container\Attributes\CurrentUser`). Routes of the account area sit in an `auth`
  group, so the user always exists — no `Auth::user() ?? abort(403)`.
- Authorization through policies: `$this->authorize(OrderAbilityEnum::CANCEL, $order)` — rule
  `access.md`.

## Steps of a process
A process of several independent steps is a list of small classes behind one interface
(`handle(Context)`), registered with a container tag and run in order by one action. A new step
is a new class, not a new branch in the action.
