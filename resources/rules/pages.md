---
paths:
  - "app/Blocks/**/*.php"
  - "resources/views/blocks/**"
  - "config/kit.php"
  - "routes/web.php"
  - "routes/ai.php"
---

# Page builder and leads (kit)

Pages of the site are built in the admin from blocks: the kit's `PagesPlugin` ("Страницы",
"Общие блоки"), `LeadsPlugin` ("Заявки"), switched on in `config/kit.php`
(`pages.enabled`, `leads.enabled`), routes — `Route::kitPages()` in `routes/web.php` (the home
page `/` named `home` and a fallback: routes of the site always win over pages).

## A block is one class and one template
- `app/Blocks/HeroBlock.php` — `final readonly class HeroBlock extends Zuber\Kit\Modules\Pages\Blocks\Block`:
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
- The kit adds "Оформление" to every block — `background` (`BlockBackgroundEnum`: page, surface,
  brand, inverse), `padding_top` / `padding_bottom` (`BlockSpacingEnum`), `anchor`, `hidden` —
  not property names of a block. The block's defaults are `background()` and `spacing()` in its
  class, the only place they are written; an empty field in the admin means "as the block".
- **A block template sets neither its vertical padding nor its background**: the kit's wrapper
  does, with the classes `block-bg-*`, `block-pt-*`, `block-pb-*` defined once in the theme
  (`resources/css/blocks.css`). Brand and inverse backgrounds redefine the colour variables there
  (`--ink`, `--muted`, `--surface`, `--line`), so templates keep using `text-ink`, `text-muted`
  and look right on any background — **only when the semantic colours are in `@theme inline`**
  (rule `blade-views.md`), otherwise text inside keeps the page colours. A highlighted card inside
  a block (a featured price plan) reuses the class: `class="block-bg-brand …"`. No raw classes or
  CSS typed in the admin.
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
and the slots `header`, `footer`; it renders the meta tags, `CanonicalUrlSupport::current()`, `@filamentStyles`,
`@filamentScripts`.

## Leads
- Lead forms are built in the admin without a programmer ("Формы заявок"): fields from Filament
  components by `LeadFieldTypeEnum` (name, phone, email, text, textarea, select, checkbox, date),
  a button text, a thank-you text, own mail addresses. A form needs a phone or an email field.
- On the site: `<livewire:kit-lead-form :form="$block->form" :source="…" />`, the block chooses
  the form with `LeadFormSelect::make('form')` (a nullable `?int $form` property); no form —
  the built-in "Обратный звонок" (name, phone, comment). Honeypot and a rate limit per IP are on.
- A popup form: `<livewire:kit-lead-modal />` once in the layout; any link `#form` (the built-in
  form) or `#form-3` (form № 3, shown in "Формы заявок") opens it — a button of any block can
  open a form without a template change.
- A lead stores a snapshot of the answers (label → value), so it stays readable after the form
  changes; contacts go to `name`/`phone`/`email` for the list and "Позвонить". The team gets a
  queued mail at the form's addresses or `kit.leads.mail_to` (`LEADS_MAIL_TO`) and, when
  `kit.leads.telegram` is set, a message in a Telegram group topic with a link to the leads in the
  admin — each channel its own queued job, so one failing does not hold the other. A site does not
  build its own lead table, form or lead notifications.
- Telegram for leads: the project passes `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` and
  `TELEGRAM_THREAD_ID` into `kit.leads.telegram` (`bot_token`, `chat_id`, `thread_id`) in its
  `config/kit.php`. Setup: the client creates a bot in @BotFather and a group with Topics, makes the
  bot an admin with "Manage topics" and writes a message there; then `php artisan kit:telegram`
  lists the chat id, `--topic="Заявки с сайта"` creates the topic and prints its id, `--test` sends
  a sample lead.

## MCP: pages without the admin
- The kit's `Zuber\Kit\Modules\Pages\Mcp\PagesServer` (package `laravel/mcp`) lets an AI
  assistant build and edit pages: `list-block-types`, `list-pages`, `get-page`, `create-page`,
  `update-page`, `set-blocks`, `add-block`, `update-block`, `move-block`, `delete-block`,
  `list-shared-blocks`, `update-shared-block`, `upload-image` (by an https link),
  `image-upload-url` (a one-time signed link for 10 minutes: the assistant sends a photo from the
  chat with `curl` from its sandbox to `_kit/pages/images/{token}`). Every write goes through the same
  actions as the admin and is checked the same way (block types, required fields, path).
- The project connects it in `routes/ai.php` — **never without authorisation**: Passport OAuth for
  claude.ai connectors and the permission that manages pages:

  ```php
  Mcp::oauthRoutes();
  Mcp::web('/mcp/pages', PagesServer::class)
      ->middleware(['auth:api', 'can:' . PermissionEnum::MANAGE_PAGES->value, 'throttle:60,1']);
  ```
- Photos from a phone are 3–8 MB: the server must accept 10 MB uploads (`upload_max_filesize`,
  `post_max_size` in PHP, `client_max_body_size` in nginx), otherwise the upload fails before the kit.
- Site tools go into a subclass: `protected array $tools = [...PagesServer::TOOLS, OwnTool::class]`.
- A new block is available to the assistant at once: `list-block-types` reads `app/Blocks`. It
  sees the constructor properties, the static field labels and options, and `example()` — so a
  meaningful example (with the formats a field expects) is what teaches it the block.

## Tests
One test renders every block of `app/Blocks` from its `example()`; a new block needs no new
test for that, only a meaningful example. The home page test renders the seeded page.
