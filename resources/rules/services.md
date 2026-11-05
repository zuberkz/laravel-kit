---
paths:
  - "app/Services/**/*.php"
---

# Services

- Business rules of the domain live in services `app/Services/<Domain>/…Service`
  (`OrderService`, `PaymentService`, `AccountService`): what is stored and where, who becomes a
  participant, what an amount is, which events and mails follow.
- `final readonly class`, dependencies through the constructor. A service works with data only
  through repositories and never opens a transaction (the action does).
- A service method decides one thing; its steps are short private methods (`storeItems()`,
  `journalEntry()`). Consequences for other subjects are methods of **their** services, called
  by the action in order (rule `actions.md`) — not chained from the source service.
- Takes and returns models and `*Data`, never raw arrays of attributes, never a `Request`.
- **A service decides, it does not label.** It returns facts — models, enums, numbers, flags
  (`OrderService::actionsFor($order, $user)` → `array<int, OrderActionEnum>`,
  `OrderService::nextStepFor()`). Interface texts, button captions, colours and modal events are
  built by the controller, the Livewire component or the view.
- A journal or history entry is a fact too: `OrderEventData(kind: OrderEventKindEnum::CANCELLED,
  reason: …)`; the readable title and text are assembled where the journal is shown.
- No interfaces by default: an interface (`app/Contracts`) appears only when a second
  implementation exists. Naming: a wrapper over one external HTTP API is a `…Client`
  (rule `clients.md`); a domain abstraction with several implementations is a `…Gateway`
  interface in `app/Contracts` (`PaymentGatewayInterface`) with implementations in `app/Gateways`
  (`FakePaymentGateway`, `KaspiPaymentGateway` — the latter goes through `KaspiClient`).
- Lists inside a method are collections: two or more operations (filter, map, sort, unique) —
  `collect($items)->filter()->map()->values()`, not nested `array_values(array_filter(array_map(…)))`.
  One simple step (`array_keys`, one `array_map`) may stay native.
- Arrays only at boundaries that require them: properties of a `*Data`, Filament `options()`, form
  state, public Livewire properties, short lists of enum cases. A collection gives them with
  `->values()->all()`; the PHPDoc type is `array<int, T>` (Larastan does not treat
  `->values()->all()` as `list<T>`). Models from the database stay Eloquent collections, no
  `->toArray()`.
