---
paths:
  - "app/Clients/**/*.php"
  - "app/Contracts/**/*.php"
  - "app/Gateways/**/*.php"
---

# Clients of external APIs

- Every external API has a client in `app/Clients/<Vendor>/` ending with `Client`:
  `app/Clients/Telegram/TelegramClient.php`. It is the only place that uses `Http`,
  `Illuminate\Http\Client` or Guzzle — the arch tests forbid them everywhere else.
- A client is transport only: build the request, send it, map the response to a `*Data`
  (`app/Data/<Vendor>/*ResponseData`). No models, no services, no business decisions — the
  service that calls the client decides what the answer means.
- `final readonly class`; base URL, token and timeouts come from `config/services.php` through the
  constructor (bind it in a service provider), never `config()` or `env()` inside methods.
- Every request has `->timeout()` and `->throw()`; transport failures become the domain's own
  exception (`TelegramUnavailableException`) with a clear message, not a raw `RequestException`.
- Controllers never use a client directly: controller → service → client.
- When the site must be able to swap the vendor (two SMS providers, a fake and a real payment
  provider), the service depends on an interface — a `…Gateway` of the domain
  (`app/Contracts/PaymentGatewayInterface.php`); each implementation lives in `app/Gateways`
  (`FakePaymentGateway`, `KaspiPaymentGateway`), is complete and goes through its vendor's client;
  the binding lives in a service provider. `…Client` is transport to one
  API, `…Gateway` is the domain abstraction over several.
- Tests: `Http::fake()` with the vendor's real response shape and `Http::preventStrayRequests()`;
  never live requests.
