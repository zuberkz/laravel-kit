---
paths:
  - "app/Filament/**/*.php"
  - "resources/css/filament/**"
  - "app/Livewire/**/*.php"
  - "resources/views/pages/**"
  - "resources/views/livewire/**"
---

# Filament 5 / Livewire

Filament is not installed by default; add it (`filament/filament`) only when the site needs an
admin panel.

## Namespaces (check the imports)
- Form fields — `Filament\Forms\Components\`; infolist entries — `Filament\Infolists\Components\`
- Layout (Grid, Section, Tabs, Wizard) — `Filament\Schemas\Components\`
- `Get`/`Set` — `Filament\Schemas\Components\Utilities\`
- Actions — `Filament\Actions\` (never `Filament\Tables\Actions\`); icons — `Filament\Support\Icons\Heroicon`

## Mandatory
- Components through static `make()`; dynamic values are closures type-hinted `Closure`,
  neighbouring fields are read through `Get $get`.
- **Every `DeleteBulkAction` has `->authorizeIndividualRecords('delete')`** — otherwise it skips
  the record policy and deletes past model events.
- Filament actions have no automatic policy check — call `authorize()` explicitly.
- `EditAction` saves attributes only — relationship repeaters need `saveRelationships()`.
- Button + modal + logic live in an action class, not in a page closure; a wizard step is its own
  class with a static `make(): Step`. Local schemas — in `Schemas/` next to the resource;
  reused fields/columns/filters — in `app/Filament/Schemas/`.
- Navigation groups — an enum (`NavigationGroupEnum`); every `Select` is `->native(false)`.
- The admin panel lives at `/panel`, never `/admin` (the first path bots try): panel id and path
  `panel`, provider `App\Providers\Filament\AdminPanelServiceProvider`. `filament:install`
  generates `AdminPanelProvider`, and the Pest Laravel preset wants every provider to end with
  `ServiceProvider` — rename it (and the line in `bootstrap/providers.php`).

## Forms on the site
- Site forms are Filament Forms inside Livewire components (`HasSchemas` + `InteractsWithSchemas`),
  no hand-made Blade field components. Field styles live in `resources/css/filament/`.
- Take the standard Filament fields wherever they exist (`TextInput`, `Textarea`, `Toggle`,
  `Radio`, `Select`, `CheckboxList`, `DatePicker`, `FileUpload`) and restyle them to the design in
  `resources/css/filament/`; do not rebuild a field Filament already has.
- Presets of a standard field (search, social link, phone) are classes with a static `make()`
  returning the configured Filament field in `app/Filament/Forms/Fields` (`SearchInput`,
  `PhoneInput`).
- A field Filament does not have (a 4-digit SMS code, a star rating, size chips) or a field with
  special behaviour (phone mask, file upload to a specific disk) is the project's own component: a
  class extending `Filament\Forms\Components\Field` (or the Filament class it replaces) in
  `app/Filament/Forms/Components` with its view in `resources/views/filament/forms/components`,
  used in the schema like any other field, so validation and state stay in the form. Markup the
  view needs comes from methods of the class (`getStars()`), not `@php`.
- There are no `x-ui` field components: every input on a screen is a Filament field. The
  showcase of all fields in every state is a Livewire component `dev.fields` on `/dev/ui`; a new
  field style or custom field goes there too.
- The deploy builds assets before `composer install`, so `vendor/` is absent during `npm run
  build`: copy what you need from the Filament package styles into `resources/css/filament/`
  instead of importing `vendor/filament/...` (or change the pipeline first).
- Field styles: `resources/css/filament/index.css` imports the Filament package styles, then one
  override file per component at the same path as its source in
  `vendor/filament/<package>/resources/css` (`support/input-wrapper.css`, `support/toggle.css`,
  `forms/radio.css`, ...), in layer `components` after the package styles. Mirror the package
  selector to win on specificity; never one growing stylesheet.
- Sizes and radii in these files are theme tokens (`h-field`, `min-h-textarea`, `rounded-field`
  from `theme.css`), not arbitrary values — the same token is used by `x-ui.button` and the fields. A schema used in several places is a class in
  `app/Filament/Schemas` (`OrderForm::configure()`).
- The form validates; its state becomes a `*Data` and goes to an action (rules `data.md`,
  `actions.md`). The component never writes to the database itself.
- The admin panel (if any) is for administrators only; account screens for users are Livewire
  pages, not panel resources.

## Pitfalls, each of which has already bitten
- `disabled()` removes the field from dehydration — add `->dehydrated(true)` when the value is
  needed, and disable neighbouring `suffixAction()`/`hintAction()` buttons with the same condition.
- **Never throw from an action handler**: the action stays mounted until reload. Catch the
  `DomainException` of the called action, report it with a notification and `return`
  (rule `exceptions.md`). In plain Livewire methods throwing is fine.
- A `#[Url]` property is still empty in `mount()` — URL-dependent checks go into `booted()`.
- `fill()` with an argument does not apply `default()`; `fillForm()` replaces the standard filling.
- A table widget's heading — `getTableHeading()`, not `$table->heading()`.
- Filament injects closure arguments **by parameter name**: write `fn (?string $state) => …`.
- A Repeater without `->live()` does not re-evaluate `visible()` of its neighbours.
- Tabs with a table or a card inside — `->contained(false)`.
- Header actions have no icon; row actions — `tableIcon()` + `groupedIcon()` with a solid icon;
  confirmations — `modalIcon()` with the outlined variant; `color()` by meaning (`danger` —
  irreversible, `warning` — risky, `gray` — auxiliary).
