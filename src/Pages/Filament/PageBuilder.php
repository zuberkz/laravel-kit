<?php

namespace Zuber\Kit\Pages\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block as BuilderBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Zuber\Kit\Pages\Blocks\Block;
use Zuber\Kit\Pages\Filament\Resources\SharedBlocks\SharedBlockResource;
use Zuber\Kit\Pages\Repositories\SharedBlockRepository;
use Zuber\Kit\Pages\Services\BlockRegistryService;
use Zuber\Kit\Pages\Services\PageRenderService;

/**
 * Конструктор блоков страницы: блоки проекта из app/Blocks и «Общий блок» из библиотеки.
 * У каждого блока — «Оформление» (якорь, скрыть), у общего — «Изменить общий блок» и «Отвязать».
 */
final class PageBuilder
{
    public static function make(string $name = 'blocks'): Builder
    {
        return Builder::make($name)
            ->hiddenLabel()
            ->blocks([
                ...collect(app(BlockRegistryService::class)->all())->map(fn (string $class): BuilderBlock => self::block($class))->values()->all(),
                self::shared(),
            ])
            ->blockIcons()
            ->blockPickerColumns(['md' => 2, 'xl' => 3])
            ->blockPickerWidth('3xl')
            ->addActionLabel(__('Добавить блок'))
            ->collapsible()
            ->collapsed(fn (string $operation): bool => $operation === 'edit')
            ->cloneable()
            ->extraItemActions([self::detachAction()]);
    }

    /**
     * Поля, которые кит добавляет к каждому блоку.
     *
     * @return array<int, Section>
     */
    public static function settings(): array
    {
        return [
            Section::make(__('Оформление'))
                ->compact()
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('anchor')
                        ->label(__('Якорь'))
                        ->prefix('#')
                        ->alphaDash()
                        ->maxLength(40)
                        ->helperText(__('Для ссылок из меню: #prices')),
                    Toggle::make('hidden')
                        ->label(__('Скрыть блок'))
                        ->helperText(__('Блок остаётся в странице, но не выводится')),
                ]),
        ];
    }

    /**
     * @param class-string<Block> $class
     */
    private static function block(string $class): BuilderBlock
    {
        return BuilderBlock::make($class::type())
            ->label(fn (?array $state): string => self::itemLabel($class::label(), $state))
            ->icon($class::icon())
            ->schema([...$class::fields(), ...self::settings()]);
    }

    private static function shared(): BuilderBlock
    {
        return BuilderBlock::make(PageRenderService::SHARED_TYPE)
            ->label(fn (?array $state): string => self::itemLabel(__('Общий блок'), $state === null ? null : [
                ...$state,
                'title' => self::sharedOptions()[is_numeric($id = Arr::get($state, 'shared_block_id')) ? (int) $id : 0] ?? null,
            ]))
            ->icon(Heroicon::OutlinedLink)
            ->schema([
                Select::make('shared_block_id')
                    ->label(__('Общий блок'))
                    ->options(fn (): array => self::sharedOptions())
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->live()
                    ->helperText(__('Правится в разделе «Общие блоки» и меняется сразу на всех страницах'))
                    ->hintAction(
                        Action::make('editSharedBlock')
                            ->label(__('Изменить общий блок'))
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->url(fn (mixed $state): ?string => is_numeric($state) ? SharedBlockResource::getUrl('edit', ['record' => (int) $state]) : null)
                            ->openUrlInNewTab()
                            ->visible(fn (mixed $state): bool => is_numeric($state)),
                    ),
                ...self::settings(),
            ]);
    }

    /**
     * «Отвязать»: общий блок копируется в страницу и дальше правится только здесь.
     */
    private static function detachAction(): Action
    {
        return Action::make('detachSharedBlock')
            ->label(__('Отвязать'))
            ->tooltip(__('Скопировать общий блок в страницу и править только здесь'))
            ->icon(Heroicon::OutlinedLinkSlash)
            ->color('gray')
            ->visible(fn (array $arguments, Builder $component): bool => Arr::get(self::itemOf($component, $arguments), 'type') === PageRenderService::SHARED_TYPE)
            ->requiresConfirmation()
            ->modalHeading(__('Отвязать общий блок?'))
            ->modalDescription(__('Его содержимое скопируется в эту страницу. Правки общего блока сюда больше не попадут.'))
            ->action(function (array $arguments, Builder $component): void {
                $state = $component->getRawState();
                $key = $arguments['item'] ?? null;
                $item = self::itemOf($component, $arguments);
                $id = Arr::get($item, 'data.shared_block_id');
                $shared = is_numeric($id) ? app(SharedBlockRepository::class)->find((int) $id) : null;

                if (! is_array($state) || ! is_string($key) || $shared === null) {
                    return;
                }

                $state[$key] = [
                    'type' => $shared->type,
                    'data' => [...$shared->data ?? [], 'anchor' => Arr::get($item, 'data.anchor'), 'hidden' => (bool) Arr::get($item, 'data.hidden')],
                ];

                $component->rawState($state);
            });
    }

    /**
     * Данные карточки конструктора, по которой нажали кнопку.
     *
     * @param  array<array-key, mixed> $arguments
     * @return array<array-key, mixed>
     */
    private static function itemOf(Builder $component, array $arguments): array
    {
        $state = $component->getRawState();
        $key = $arguments['item'] ?? null;
        $item = is_array($state) && is_string($key) ? ($state[$key] ?? null) : null;

        return is_array($item) ? $item : [];
    }

    /**
     * @return array<int, string>
     */
    private static function sharedOptions(): array
    {
        return once(fn (): array => app(SharedBlockRepository::class)->options());
    }

    /**
     * Заголовок карточки блока: тип, его заголовок и пометка «скрыт».
     *
     * @param array<string, mixed>|null $state
     */
    private static function itemLabel(string $label, ?array $state): string
    {
        $title = Arr::get($state ?? [], 'title');
        $label = is_string($title) && $title !== '' ? $label . ' — ' . str($title)->limit(60) : $label;

        return Arr::get($state ?? [], 'hidden') === true ? $label . ' · ' . __('скрыт') : $label;
    }
}
