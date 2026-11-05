---
paths:
  - "app/Enums/**/*.php"
---

# Enum classes

- The class name ends with `Enum`; cases are `UPPER_SNAKE_CASE` (this project rule wins over the
  generic TitleCase advice); enums are backed (usually string). The arch tests check it.
- Enums are grouped by domain in subfolders: `App\Enums\Orders`, `App\Enums\Payments`,
  `App\Enums\Access` — a new one goes into its folder.
- Every controlled vocabulary is a backed enum + a model cast. No free-form status/type strings
  in columns, no constants instead of an enum, and no string statuses passed around in code —
  only the enum.
- A two-value flag is a boolean column, not a two-case enum.
- The readable name is `getLabel()` implementing Filament's `HasLabel` once Filament is installed
  (the same enum then works in forms and tables), `label()` before that. Derived behaviour —
  methods of the enum (`color()`, `isFinal()`, `canTransitionTo()`) with an exhaustive `match`,
  never a `match` on the enum in another class: a new case without a branch fails at once.
- Labels go through `__()`, except enums read by config files: the config is built before the
  translator exists.
- Validate incoming values with `#[Enum(XEnum::class)]` on the `*Data` property or by typing the
  property with the enum.

```php
enum OrderStatusEnum: string
{
    case NEW = 'new';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NEW => __('Новый'),
            self::PAID => __('Оплачен'),
            self::CANCELLED => __('Отменён'),
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::NEW => false,
            self::PAID, self::CANCELLED => true,
        };
    }
}
```
