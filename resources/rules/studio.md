# Studio conventions

Shared by every site of the studio; the file comes from `zuberkz/laravel-kit` (`.claude/rules/kit/`)
and is overwritten on `composer update` — change it in the kit, not here. What is specific to
one site lives in its `CLAUDE.md` and in `.claude/rules/*.md` outside `kit/`.

The kit also holds the arch tests (`tests/Arch/KitArchTest.php`), `pint.json`, the base
`DomainException` with its handling and the deploy workflow. An improvement that every site needs
(a rule, an arch test, a deploy fix) goes into the kit: edit `../laravel-kit`, release a tag, run
`composer update zuberkz/laravel-kit` in the sites — never patch one site's copy.

## Checks are part of the build

Every push runs the checks in GitHub Actions before the deploy: `pint --test`, Larastan
(`phpstan.neon`), Pest with the architecture tests in `tests/Arch`. A failed check stops the
deploy — read the failing step, fix the code, push again. Never weaken a check, an arch rule or
the Larastan level to get green, and never add a `@phpstan-ignore` or a baseline: fix the code.
Pint only on the files you changed: `vendor/bin/pint <files>`.

## Laravel Boost (MCP)

The `laravel-boost` MCP server (`.mcp.json`, runs `php artisan boost:mcp` in the `php-fpm`
container) knows this application. Use it instead of guessing:

- `search-docs` before using a Laravel / Livewire / Filament / Pest / Tailwind API — it searches
  the docs of the installed versions, which matter;
- `application-info` to see the installed packages and versions before relying on an API;
- `database-schema` before writing a migration, a model or a repository;
- `database-query` (read-only) to look at data instead of writing throwaway scripts;
- `last-error`, `read-log-entries` and `browser-logs` when something fails;
- `get-absolute-url` before giving the user a link to the local site.

Routes and commands — `php artisan route:list`, `php artisan list` in the container.

When a task is done and the checks pass, run the Boost prompt `laravel-code-simplifier` on the
files you changed: behaviour stays the same, the code becomes plainer. These rules win over its
general advice where they differ.

## Layers

Reading: route → controller / Livewire component → **service** → **repository** → model.
Writing: route → controller / Livewire component → **action** (transaction) → **service** →
**repository** → model. No skipping a layer, no calls upwards.

| Layer | Path | Rule |
|-------|------|------|
| Routes | `routes/web.php` | Only routing; a static page is `Route::view()`, no closures with logic |
| Controllers | `app/Http/Controllers` | Thin: take a `*Data`, call an action (write) or a service (read), return a response — rule `actions.md` |
| Livewire | `resources/views/pages`, `resources/views/livewire` | Same as controllers, plus UI state — rule `livewire.md` |
| Data (DTO) | `app/Data/<Domain>` | spatie/laravel-data — rule `data.md` |
| Actions | `app/Actions/<Domain>` | One user scenario that writes, `handle()`, owns the transaction — rule `actions.md` |
| Services | `app/Services/<Domain>` | Business rules, suffix `Service` — rule `services.md` |
| Repositories | `app/Repositories` | **The only place with database queries**, one per table — rule `repositories.md` |
| Clients | `app/Clients/<Vendor>` | The only place that talks HTTP to an external API — rule `clients.md` |
| Gateways | `app/Gateways` | Implementations of a domain contract with several vendors (`…Gateway`) — rule `clients.md` |
| Enums | `app/Enums/<Domain>` | Suffix `Enum`, `UPPER_SNAKE_CASE` cases — rule `enums.md` |
| Policies | `app/Policies` | Access by permission, abilities are enum cases — rule `access.md` |
| Exceptions | `app/Exceptions/<Domain>` | Own classes only; refusals extend the kit's `DomainException` — rule `exceptions.md` |
| Jobs | `app/Jobs` | Thin, with `$tries`/`$timeout`/`backoff()` — rule `queues.md` |
| Commands | `app/Console/Commands` | One-off and scheduled operations; data work is never a migration — rule `commands.md` |
| Notifications | `app/Notifications`, `app/Mail` | Always queued, after commit — rule `notifications.md` |
| Contracts | `app/Contracts` | Interfaces only, suffix `Interface`, bound in a service provider |
| Support | `app/Support` | Stateless helpers without a domain (formatting, parsing), suffix `Support` |
| Migrations | `database/migrations` | Schema only; every index answers a repository query — rule `migrations.md` |
| Models | `app/Models` | Attributes, casts, relations; no queries, no scopes, no business rules — rule `repositories.md` |
| Views | `resources/views` | Blade, display only — rule `blade-views.md` |

`App\Http` is used only inside `App\Http` and `App\Providers` (Pest Laravel preset): services
never receive a `Request`, they receive a `*Data`.

### A feature end to end

The shape of a write scenario (a form that creates a record):

```php
// routes/web.php
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:5,1')->name('orders.store');

// app/Http/Controllers/OrderController.php — validation is the DTO, writing is the action
final class OrderController extends Controller
{
    public function store(OrderRequestData $data, PlaceOrderAction $placeOrder): RedirectResponse
    {
        $placeOrder->handle($data);

        return to_route('home')->with('status', __('Заказ принят.'));
    }
}

// app/Actions/Orders/PlaceOrderAction.php — the scenario: which services, in which order, one transaction
final readonly class PlaceOrderAction
{
    public function __construct(
        private ConnectionInterface $db,
        private OrderService $orders,
        private NotificationService $notifications,
    ) {}

    public function handle(OrderRequestData $data): Order
    {
        $order = $this->db->transaction(fn (): Order => $this->orders->place($data));

        $this->notifications->orderPlaced($order);

        return $order;
    }
}

// app/Services/Orders/OrderService.php — the business rules
final readonly class OrderService
{
    public function __construct(private OrderRepository $orders) {}

    public function place(OrderRequestData $data): Order
    {
        return $this->orders->create(OrderData::fromRequest($data), OrderStatusEnum::NEW);
    }
}

// app/Repositories/OrderRepository.php — the only place that touches the table
final readonly class OrderRepository
{
    public function create(OrderData $data, OrderStatusEnum $status): Order
    {
        return Order::query()->create([...$data->toArray(), 'status' => $status]);
    }
}
```

Tests for it: a feature test of the route (success, validation errors, database state) and of
every refusal of the action — rule `testing.md`.

## SOLID in practice

- **Single responsibility.** A class changes for one reason. A service serves one subject; when
  its methods stop sharing a subject, split it. A method does one thing — over ~30 lines or more
  than one level of abstraction means extract.
- **Open/closed.** A new variant is a new class behind an interface (tag them in a service
  provider) or a new enum case with an exhaustive `match`, not another `if` in an existing method.
- **Liskov.** An implementation honours its interface completely; no "not supported" exceptions.
- **Interface segregation.** Small interfaces for one role; a class does not implement methods
  it does not need.
- **Dependency inversion.** Dependencies come through the constructor and the container; no
  `new SomeService()`, no `app()` or facades inside services when a constructor argument will do.
  An interface appears only when there is a second implementation.

## Conventions

- Every class name says what it is: `*Data`, `*Service`, `*Client`, `*Action`, `*Enum`,
  `*Interface`, `*Gateway`, `*Support`, `*Controller`, `*Exception`, `*Repository`, `*Policy`,
  `*Command`, `*Job`, `*Notification`. The suffix and
  the directory go both ways: a `*Service` lives only in `app/Services`, a class in `app/Services`
  ends only with `Service`. The arch tests check it, together with the layer boundaries and
  `final readonly` for services, clients, actions and repositories.
- Incoming data — laravel-data `*Data` objects, **never FormRequest** (there is no
  `app/Http/Requests`). Responses of an external API and results of services are `*Data` or
  models, not arrays.
- **No `declare(strict_types=1)`**, but everything is typed: arguments, properties and return
  types of every method and function (anonymous ones included). Arrays get a PHPDoc shape
  (`@param array<string, int>`, `@return array<int, OrderData>`).
- Constructor property promotion; `readonly` for services and data that never change after
  construction; `final` for classes not designed for inheritance.
- Braces always; early return instead of nested `if`/`else`; no `else` after `return`; no nested
  ternaries — `match` instead.
- Collections over native array functions: two or more operations on a list are
  `collect()->filter()->map()->values()`; a single native call is fine.
- PHPDoc over inline comments; a comment explains **why**, the code says what. No commented-out
  code, no `dd`/`dump`/`ray`, no `env()` outside `config/` (the arch tests check it).
- Config values through `config()`; secrets only in `.env`, never in the repository.
- Any controlled vocabulary is a backed enum + a model cast; no magic strings and numbers.
- Money — `decimal(14,2)` stored as is (`5000.00` = 5 000 ₸), arithmetic through `bcmath`, never
  floats.
- Errors: a refusal by a business rule is a `DomainException` subclass, a failure is its own
  exception; only own classes are thrown, rendering is in one place — rule `exceptions.md`.
- Do not add a package when Laravel already does the job; a new package needs a reason in the
  commit body.

## Commits

Conventional Commits, description in Russian, verb in the infinitive:
`feat(orders): добавить оформление заказа`. One commit — one logical change; tests go in the same
commit as the code they check.
