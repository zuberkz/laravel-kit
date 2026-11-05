---
paths:
  - "app/Exceptions/**/*.php"
  - "app/Services/**/*.php"
  - "app/Actions/**/*.php"
  - "app/Clients/**/*.php"
  - "bootstrap/app.php"
  - "app/Http/Setup/**/*.php"
---

# Exceptions

## Two kinds
- **A refusal by a business rule** ("the order is already paid", "the time slot is taken") is a
  normal outcome, not a failure. Its exception extends `Zuber\Kit\Exceptions\DomainException` (package
  `zuberkz/laravel-kit`), lives in
  `app/Exceptions/<Domain>/` and carries a message for the user, translated with `__()`. It is
  logged as statistics, not as an error: channel `domain` (`storage/logs/domain-*.log`, level
  `info`, 30 days) with the class, `context()`, the user and the URL — `laravel.log` stays for
  real failures.
- **A failure** (an external API is down, data is broken, an invariant is violated) is a real
  error: its own exception (`KaspiUnavailableException`, extending `RuntimeException`), reported
  with context. A client turns transport errors into it (rule `clients.md`).

## Throwing
- The application throws only its own classes — never a bare `\Exception`, `\RuntimeException`,
  `\LogicException`, `\InvalidArgumentException`; the arch tests check it.
- Create through a named constructor, so the message and the context are written once:

```php
final class OrderAlreadyPaidException extends DomainException
{
    private function __construct(private readonly Order $order)
    {
        parent::__construct(__('Заказ :number уже оплачен.', ['number' => $order->number]));
    }

    public static function for(Order $order): self
    {
        return new self($order);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return ['order_id' => $this->order->id];
    }
}

// in a service
if ($order->status->isFinal()) {
    throw OrderAlreadyPaidException::for($order);
}
```

- Context for the log (ids, the user) goes into `context()`, not glued into the message.
- Invalid input is not an exception: validation of the `*RequestData` handles it before a
  service is called.

## Handling — one place
- `Zuber\Kit\Setup\ExceptionsSetup` from the kit (passed to `withExceptions()` in `bootstrap/app.php`) renders
  every `DomainException`: a regular request goes back with the input and the message in the
  session key `error`; a JSON request gets 422 with `{"message": …}`. Reporting of `DomainException` goes to the `domain` channel and stops there
  (`->report(…)->stop()`), so it never reaches the error log. The layout shows `session('error')`.
- `bootstrap/app.php` only assembles the application: middleware setup lives in an invokable
  `App\Http\Setup\MiddlewareSetup` once the site configures middleware, so the file does not grow
  into logic nobody tests. A site that needs more exception handling writes its own
  `App\Http\Setup\ExceptionsSetup` that calls the kit's one first; the kit's behaviour itself is
  changed in the kit, for every site.
- The `domain` log channel comes from the kit's service provider; a channel with the same name in
  `config/logging.php` overrides it.
- Many refusals of one kind in `domain.log` are a signal about the product (a confusing form, a
  missing option), not about the code — look at it when analysing a site.
- Controllers do not wrap calls in `try/catch`. Nobody catches `Throwable` or `Exception` to hide
  an error; catch only an exception you can handle meaningfully, and as narrowly as possible.
- A Livewire component or a Filament action catches **only** `DomainException`, calls
  `report($e)` (so the refusal still lands in the `domain` log) and shows it as a notification
  (`Notification::make()->danger()->title($e->getMessage())->send()`); a Filament
  action handler then returns instead of rethrowing (rule `filament-ui.md`). Everything else
  propagates and is logged.
- A queued job lets failures propagate (the queue retries and records them); a refusal that will
  never succeed is handled with `$this->fail($e)` instead of endless retries.

## Tests
Every refusal of an action is tested: which exception (or which message the user saw), the
status/redirect, and that the database did not change.
