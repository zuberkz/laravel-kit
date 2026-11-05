---
paths:
  - "resources/views/**/*.blade.php"
  - "resources/css/**"
  - "app/Providers/**/*.php"
---

# Blade views

The template ships with Laravel's stock `welcome.blade.php`; replace it with the site's layout
and pages.

## Visual quality — the page must look designed, not assembled
A studio site is judged by its look. "It renders" is not done; done is a page that looks
deliberate on a phone and on a wide screen.

- **Design system first.** Before the first page define tokens in `resources/css/theme.css`
  (`@theme`): a palette of 2–3 brand colours with tints, neutral greys, semantic colours
  (success, warning, danger), a type scale, radii, shadows, a spacing rhythm. Every template uses
  only these tokens; no ad-hoc `#hex`, no arbitrary `px` values.
- **Typography carries the page**: a real font pair (display + text) loaded with `font-display:
  swap`, a clear scale (h1 > h2 > h3 > body > small, not five similar sizes), comfortable line
  length (`max-w-prose` for text), line height 1.5–1.7 for body, tighter for headings, Russian
  typography (« », non-breaking spaces before dashes and after short words).
- **Whitespace and rhythm**: generous section padding (`py-16 md:py-24`), one spacing scale,
  aligned to a container grid. Crowded blocks and uneven gaps are the first sign of an
  unfinished page.
- **Hierarchy**: one primary action per screen, visibly stronger than secondary ones; headings
  short; supporting text muted (`text-muted` token), not the same weight as the headline.
- **Every interactive element has every state**: hover, focus-visible (a visible ring, never
  removed), active, disabled, loading (`wire:loading`), and transitions of 150–300 ms on colour and
  transform only. Forms show errors inline, next to the field, in the danger colour with an icon.
- **Empty, loading and error states are designed**, not left blank: a short explanation and an
  action ("Заказов пока нет — оформить первый").
- **Mobile first**: build at 375 px, then `md:` and `lg:`. No horizontal scroll, tap targets at
  least 44 px, readable text without zoom (≥ 16 px body).
- **Dark theme** comes for free when templates use tokens only (`bg-page`, `bg-surface`,
  `text-ink`); check contrast in both themes (WCAG AA: 4.5:1 for text).
- **Images**: modern formats (WebP/AVIF) at the displayed size with a 2× variant, `aspect-ratio`
  reserved to avoid layout shift, real content instead of grey placeholders when it exists.
- **Details that make it look finished**: consistent icon set and stroke width, consistent
  radius on cards, buttons and inputs, subtle shadows instead of heavy borders, no default
  browser look for selects, checkboxes and file inputs.
- **Check it in a browser**, not only in tests: open the page at 375, 768 and 1280 px, in both
  themes, and look at it as a client would before calling the task done.

## Templates hold no PHP logic
- **No `@php` blocks** in templates and components — not for calculations, not for variables, not
  for type docblocks. Conditions, formatting and data come ready from the controller, the Livewire
  class or a view composer; the template asks predicates of the DTO, the model or the enum
  (`@if ($order->isOverdue())`, `{{ $order->status->label() }}`), never rebuilds a condition.
- No queries and no business conditions in templates (no `Model::…`, no `->where()`, no
  `auth()->user()->role === …` — permissions through `@can`).
- Every user-facing string goes through `__()`; `{{ }}` escapes, `{!! !!}` only for HTML the code
  produced itself or passed through a sanitizer.

## Layout split into components
- Pages extend a layout component `resources/views/components/layouts/app.blade.php`
  (`<x-layouts.app :title="..." :description="...">`): `<head>` with the unique `<title>` and
  `<meta name="description">`, `@vite`, the header and the footer as their own components
  (`<x-layouts.header />`, `<x-layouts.footer />`).
- **A template is never one long file.** Every visual block — header, hero, a card, a list row,
  a section — is its own component; a page is a short list of components.
- Base components — anonymous components in `resources/views/components/ui` (`x-ui.button`,
  `x-ui.input`, `x-ui.card`, `x-ui.icon`), every file starting with a Blade comment `{{-- --}}`
  describing its props (with types of non-scalar ones), slots and variants. Only the base elements
  used everywhere (button, icon, input wrapper, badge) sit in the root of `components/ui`;
  everything else goes to a subfolder named after its section — `ui/layout` (header, nav),
  `ui/catalog`, `ui/order`, `ui/cabinet`, … — without repeating the section in the file name:
  `ui/order/card.blade.php` is `<x-ui.order.card>`, not `order-card`. A showcase page with
  every variant (`/dev/ui`, only in the `local` environment) is where they are checked; a new base
  component goes there too.
- Blocks of one page live in a sibling directory named after the view (`home.blade.php` +
  `home/hero.blade.php`), registered in `AppServiceProvider` with
  `Blade::anonymousComponentPath(resource_path('views/home'), 'home')` **with a prefix**, used as
  `<x-home::hero />`. Without a prefix the parts become global and collide by name.
- **`x-…` components are markup only**: no queries, no state. Anything with state or data is a
  Livewire component (rule `livewire.md`).

## Laravel's own Blade practices
- **A directive or a helper instead of a hand-written condition**: `@isset($order->discount)` (not
  `@if ($order->discount !== null)` — `isset` is false only for `null`, so 0 still shows),
  `@empty($category)` / `@unless`, `@forelse … @empty` for a list with an empty state,
  `@auth` / `@guest`, `@can`, `@class([...])`, `@checked` / `@selected` / `@disabled` /
  `@readonly` / `@required` on inputs, `@use` for imports. In PHP and in templates — Laravel's
  helpers `filled()` / `blank()` (empty string, whitespace, empty array count as blank),
  `optional()` / `?->`, `str()`, `collect()`, not their raw equivalents.
- Prefer components over `@include`: a component has an explicit interface — `@props` with
  defaults, the attribute bag, slots. `@include` only for a tiny partial that deliberately uses
  the current view data, with an explicit data array.
- Let callers add attributes: the root element of a component uses
  `{{ $attributes->merge(['class' => '…']) }}` (classes are merged, not replaced);
  `$attributes->class([...])` for conditional classes by props.
- Variants of a component are props with a fixed set of values mapped to classes by
  `$attributes->class(['…' => $variant === 'primary'])` — not separate near-identical components.
- A nested component reads a prop given to its parent with `@aware`.
- A script or style a component needs goes through `@pushOnce` (with a stable name), so a
  component rendered ten times adds it once.
- Data several views need on every render (the menu, contacts in the footer) comes from a view
  composer registered in a service provider for those views — not repeated in every controller.
- For partial updates (htmx, Turbo) return a named `@fragment` with `->fragmentIf(...)`.

## Tailwind (v4): theme file and CSS per component
- `resources/css/app.css` is the entry point and holds **only imports and `@source`**:
  `@import 'tailwindcss'`, `./theme.css`, `./base.css`, then component files.
- `resources/css/theme.css` is the theme: every colour, font, radius and shadow as `@theme`
  tokens; semantic colours (`page`, `surface`, `ink`, `muted`, `line`, `brand`) read CSS variables,
  `.dark` redefines the variables. Templates use only these tokens — no raw palette
  (`bg-gray-100`), no `#hex`, no arbitrary values (`p-[13px]`).
- `resources/css/base.css` — `@layer base` for elements (body, headings, focus ring, links in
  prose).
- Styles of one component that Tailwind classes cannot express well (prose from the admin panel,
  a third-party widget, a complex animation) go into `resources/css/components/<name>.css`,
  imported in `app.css`; a reusable composite is an `@utility`. Never one growing stylesheet.
- Classes stay in templates; no inline `style=""`. Long class lists repeated in several places
  mean a missing `x-ui` component, not a CSS class.

## Pages
- Forms without Filament: `@csrf`, `old()` for values, `@error` next to the field, labels tied to
  inputs.
- Assets through `@vite`. Images have `alt`, `width`/`height` and `loading="lazy"` below the fold.
- SEO on every page: a unique `<title>` and `<meta name="description">`, one `<h1>`.
