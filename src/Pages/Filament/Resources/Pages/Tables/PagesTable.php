<?php

namespace Zuber\Kit\Pages\Filament\Resources\Pages\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Zuber\Kit\Pages\Actions\DuplicatePageAction;
use Zuber\Kit\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Pages\Filament\Resources\Pages\PageResource;
use Zuber\Kit\Pages\Models\Page;

final class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Страница'))
                    ->description(fn (Page $record): string => $record->path)
                    ->searchable(['title', 'path'])
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Статус'))
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label(__('Изменена'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('Статус'))->options(PageStatusEnum::class),
            ])
            ->recordActions([
                self::previewAction(),
                self::duplicateAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    public static function previewAction(): Action
    {
        return Action::make('preview')
            ->label(__('Открыть'))
            ->tooltip(__('Открыть страницу на сайте, черновик тоже'))
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (Page $record): string => PageResource::previewUrl($record))
            ->openUrlInNewTab();
    }

    public static function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label(__('Копия'))
            ->tooltip(__('Создать черновик с теми же блоками'))
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->visible(fn (): bool => PageResource::canCreate())
            ->action(function (Page $record, DuplicatePageAction $duplicate, Action $action): void {
                $copy = $duplicate->handle($record);
                $action->redirect(PageResource::getUrl('edit', ['record' => $copy]));
            });
    }
}
