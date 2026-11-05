---
paths:
  - "app/Blocks/**/*.php"
  - "resources/views/blocks/**"
  - "config/kit.php"
  - "routes/web.php"
---

# Page builder and leads (kit)

Pages of the site are built in the admin from blocks: the kit's `PagesPlugin` ("Страницы",
"Общие блоки"), `LeadsPlugin` ("Заявки"), switched on in `config/kit.php`
(`pages.enabled`, `leads.enabled`), routes — `Route::kitPages()` in `routes/web.php` (the home
page `/` named `home` and a fallback: routes of the site always win over pages).

## A block is one class and one template
- `app/Blocks/HeroBlock.php` — `final readonly class HeroBlock extends Zuber\Kit\Pages\Blocks\Block`:
  constructor properties are the block's data, `label()` is its name in the admin, `fields()` the
  Filament fields **with the same names as the properties**, `example()` sample data, `icon()` a
  Heroicon. The type in the page data and the template name come from the class name
  (`TextImageBlock` → `text-image`); the kit finds the classes in `app/Blocks` itself.
- `resources/views/blocks/hero.blade.php` gets `$block` — the typed object. Read properties and
  call its methods (`$block->paragraphs()`, `$block->hasButton()`); no `@php`, no `data_get`,
  no array keys of the stored JSON. Images — `<x-kit::image :path="$block->image" :alt="…" />`
  (`eager` on the first screen), uploads — the kit's `BlockImageUpload::make()`.
- Optional fields have defaults in the constructor; a required property without a value makes
  the block skipped and reported, the page still renders. Renaming or removing a property is a
  data change: old pages keep the old key — keep a default or migrate the stored data.
- `anchor` and `hidden` are added by the kit to every block — not property names of a block.
- Interactivity inside a block is a Livewire component in its template (the lead form below);
  the block itself stays plain Blade.

## Shared blocks
- A section repeated on many pages (a lead form, advantages) is a shared block: edited once in
  "Общие блоки", placed on a page as "Общий блок". "Отвязать" copies it into the page.
- The site header and footer are shared blocks with the position "Шапка"/"Подвал": rendered on
  every page into the layout slots `header`/`footer`; a page can switch them off.
- Never copy the same content into many pages by hand.

## The layout
`kit.pages.layout` (default `layouts.app`) gets `title`, `description`, `ogImage`, `noindex`
and the slots `header`, `footer`; it renders the meta tags, `canonical_url()`, `@filamentStyles`,
`@filamentScripts`.

## Leads
- The form is `<livewire:kit-lead-form :source="…" :button-label="…" />` (name, phone, comment,
  honeypot, a rate limit per IP): a lead is stored, the team gets a queued mail at
  `kit.leads.mail_to` (`LEADS_MAIL_TO` in `.env`, comma-separated), managers work with it in
  "Заявки". A site does not build its own lead table or form.

## Tests
One test renders every block of `app/Blocks` from its `example()`; a new block needs no new
test for that, only a meaningful example. The home page test renders the seeded page.
