---
paths:
  - "resources/views/pages/**"
  - "resources/views/livewire/**"
  - "app/Livewire/**/*.php"
---

# Livewire components

Livewire 4 is not installed by default; add it when the site needs interactive screens. The
layer rules stay the same: a component reads through services and writes through actions
(rule `actions.md`).

## Multi-file components (MFC) — mandatory
Every component, page components included, is a **multi-file component**: a folder `⚡name/` next
to the other views, holding
- `name.php` — the class (an anonymous class `new class extends Component { … }`), state, actions,
  computed properties;
- `name.blade.php` — the template, display only;
- `name.js` — only when the component needs its own JavaScript;
- `name.test.php` — optional, the component's Pest test next to it.

```
resources/views/pages/orders/⚡index/index.php
resources/views/pages/orders/⚡index/index.blade.php
resources/views/livewire/orders/⚡cancel-modal/cancel-modal.php
resources/views/livewire/orders/⚡cancel-modal/cancel-modal.blade.php
```

- Right after installing Livewire set the generator to MFC in `config/livewire.php`:
  `'make_command' => ['type' => 'mfc', 'emoji' => true]`, then create components only with
  `php artisan make:livewire orders.cancel-modal` (and `pages::orders.index` for a page).
- **Never** single-file components (class and template in one `.blade.php`) and never class-based
  components in `app/Livewire` with a separate view: one shape for the whole project. Shared
  behaviour of several components is a trait in `app/Livewire/Concerns`.
- Moving or renaming a component moves the whole folder; the folder name, the files and the tag
  (`<livewire:orders.cancel-modal />`) stay in sync.

## Composition
- Pages are page components (`Route::livewire`, `resources/views/pages`); modals and blocks with
  logic live in `resources/views/livewire`.
- **Every screen of the site is a Livewire page component, static ones included** (a text page,
  a thank-you page): `Route::livewire('/about', 'pages::about')`, never `Route::view()`. The page
  sets its title in `mount()`, so a static screen can gain state without changing the route.
  `Route::view()` stays only for the local `/dev/ui` showcase.
- A screen's route is added to `routes/web.php` in the same commit as the screen, with a name
  (`orders.show`; inside the `{locale}` group when the site has languages); links between screens
  use `route()`.
- Everything with its own state or logic (data from the database, search, filters, actions,
  access checks) is a separate component: a tab, a modal, a list block. A page only assembles
  them; switching between them is `<livewire:is :component="…">`, not an `@if/@elseif` chain
  passing arrays of data.
- A page holds its title and meta, the page header, the access check and the composition of
  components. Search, filters, queries and row captions do not live in the page. The page
  template has its own root `<div>`: when the whole template is one `<livewire:…>`, method calls
  go to the page instead of the child.
- A page with a single form (login, registration, password reset) may hold the form itself, but
  the logic is in actions and services (`LoginAction`): no `Auth::attempt`, `RateLimiter`,
  `Password::…` in the page. A page with several independent forms assembles them from
  components (`account.profile`, `account.password`, `account.deletion`).
- Row captions, button texts and modal texts are built by the component from the facts the
  service returns (`OrderService::actionsFor()` → buttons), not by the model, not by the service.
  A row is a Blade component, not a Livewire component per row.
- Filters of a list come from an enum method (`OrderFilterEnum::cases()`), not strings in the
  template; summaries and totals are DTOs; captions are component methods.
- A button inside a Blade component dispatches an event: `$dispatch({{ Js::from($event) }},
  {{ Js::from($params) }})`. Directives (`@js`) are not compiled inside `<x-…>` attributes — use
  `{{ }}`.
- A component catches only `DomainException` from an action and shows its message as a
  notification; everything else propagates (rule `exceptions.md`).
- No `@php` logic in templates: computations and conditions are computed properties and methods
  of the class. Permissions through `@can` (policies), never by comparing roles in a template.
- `view()` is taken by Livewire in a multi-file class — name computed properties differently
  (`card()`).
- A record id that must not change on the client is `#[Locked]`; the component re-checks the
  permission before every write (the record may have changed while the modal was open).
- One modal — one purpose (`order-cancel`, `order-payment`), not one modal for every mode with a
  `definitions()` array. Modes are an enum (name and ability); the explanation, the button text
  and the notification are methods of the modal; it opens by its own event with the record id.
  After an action the modal dispatches an update event (`order-updated`) and the tabs re-read the
  record. Modals on a page have a constant `key`, otherwise a page re-render mounts them again.
- Forms in components — Filament Forms (`HasSchemas` + `InteractsWithSchemas`), rule
  `filament-ui.md`.
