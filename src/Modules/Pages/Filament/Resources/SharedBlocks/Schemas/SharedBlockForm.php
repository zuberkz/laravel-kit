<?php

namespace Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Zuber\Kit\Modules\Pages\Enums\SharedBlockPositionEnum;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\Services\BlockRegistryService;

final class SharedBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        $registry = app(BlockRegistryService::class);

        return $schema
            ->columns(1)
            ->components([
                Section::make(__('Общий блок'))
                    ->icon(Heroicon::OutlinedSquare2Stack)
                    ->description(fn (?SharedBlock $record): string => $record === null
                        ? __('Правится здесь и меняется сразу на всех страницах, где стоит.')
                        : trans_choice('Стоит на :count странице|Стоит на :count страницах|Стоит на :count страницах', (int) $record->pages_count, ['count' => (int) $record->pages_count]))
                    ->columns(['md' => 3])
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Название'))
                            ->helperText(__('Видно только в админке: «Форма заявки — услуги»'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('Блок'))
                            ->options($registry->labels())
                            ->native(false)
                            ->required()
                            ->live()
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Select::make('position')
                            ->label(__('Где стоит'))
                            ->options(SharedBlockPositionEnum::class)
                            ->placeholder(__('На выбранных страницах'))
                            ->helperText(__('Шапка и подвал выводятся на всех страницах сами'))
                            ->native(false)
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make(__('Содержимое'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (Get $get): bool => filled($get('type')))
                    ->schema([
                        Group::make()
                            ->statePath('data')
                            ->schema(fn (Get $get): array => self::fields($registry, $get('type'))),
                    ]),
            ]);
    }

    /**
     * Поля выбранного блока.
     *
     * @return array<int, mixed>
     */
    private static function fields(BlockRegistryService $registry, mixed $type): array
    {
        $class = is_string($type) ? $registry->find($type) : null;

        return $class === null ? [] : $class::fields();
    }
}
