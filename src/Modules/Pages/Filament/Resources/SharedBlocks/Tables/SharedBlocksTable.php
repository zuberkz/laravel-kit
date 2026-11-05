<?php

namespace Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\Services\BlockRegistryService;

final class SharedBlocksTable
{
    public static function configure(Table $table): Table
    {
        $labels = app(BlockRegistryService::class)->labels();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Название'))
                    ->description(fn (SharedBlock $record): string => $labels[$record->type] ?? $record->type)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label(__('Где стоит'))
                    ->badge()
                    ->placeholder(__('На страницах')),
                TextColumn::make('pages_count')
                    ->label(__('Страниц'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Изменён'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
