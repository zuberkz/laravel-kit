---
paths:
  - "app/Data/**/*.php"
  - "app/Http/Controllers/**/*.php"
  - "app/Services/**/*.php"
  - "app/Actions/**/*.php"
---

# Data objects (spatie/laravel-data)

- Data passes between layers as `Spatie\LaravelData\Data` objects in `app/Data/<Domain>/` ending
  with `Data`: `OrderRequestData` (incoming), `OrderData` (a new record / an entity),
  `*ResponseData` (an external API answer or an outgoing payload). Properties are typed, statuses
  and types are enums. Not in `App\Http` — services use them.
- Incoming data is a `*RequestData` injected into the controller method by type hint: it
  validates the request itself, a failure redirects back with errors (JSON — 422). No
  FormRequest, no `$request->validate()`, no `$request->input()` in controllers.
- **Validation rules are attributes on the properties** — `#[Max(100)]`, `#[Email]`,
  `#[Regex(...)]`, `#[Between(1, 10)]`, `#[In(...)]`, `#[Enum(StatusEnum::class)]`, imported from
  `Spatie\LaravelData\Attributes\Validation\`. `required`, `nullable`, the type and the boolean
  rule are inferred from the PHP type: `string $name` is required, `?string $email = null` is
  optional. `rules()` only for what attributes cannot express (`'items.*' => [...]`,
  `required_without`).
- **Every form and every endpoint has its own `*RequestData`**, even when the fields coincide
  today: contracts change independently.
- A Filament form validates itself; its state becomes a DTO with `XxxData::from($state)` or a
  `fromForm()` when the form is shaped differently — the DTO does not repeat the rules.
- A DTO of a new record carries `#[MapOutputName(SnakeCaseMapper::class)]`: `toArray()` gives the
  table columns, the repository passes it to `create()` as is (rule `repositories.md`). Build it
  from the request in a named constructor: `OrderData::fromRequest(OrderRequestData $data)` —
  normalisation (trimmed text, empty → `null`) happens there, once.
- A JSON column with a structure is cast to a Data class on the model (`'delivery' =>
  DeliveryData::class`); keys are stored in snake_case (`#[MapName(SnakeCaseMapper::class)]`).
- Field names: a web form uses snake_case inputs mapped by
  `#[MapInputName(SnakeCaseMapper::class)]`; a JSON API is camelCase both ways.
- Money in transport — `mixed` + `#[Numeric, Min(0)]` + `/** @var float */`: union types break
  rule inference.
- Mutually exclusive or derived values are predicates of the DTO (`hasMessage()`), not a second
  check in a template or a controller.
- A DTO holds data and small predicates; no queries, no services, no side effects.

```php
#[MapInputName(SnakeCaseMapper::class)]
final class OrderRequestData extends Data
{
    public function __construct(
        #[Max(100)]
        public string $name,
        #[Regex('/^\+?[0-9\s()-]{7,20}$/'), Max(20)]
        public string $phone,
        #[Email, Max(255)]
        public ?string $email = null,
        #[Max(2000)]
        public ?string $comment = null,
    ) {}
}

#[MapOutputName(SnakeCaseMapper::class)]
final class OrderData extends Data
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public ?string $comment,
    ) {}

    public static function fromRequest(OrderRequestData $data): self
    {
        return new self($data->name, $data->phone, $data->email, filled($data->comment) ? trim($data->comment) : null);
    }
}
```
