<?php

namespace Zuber\Kit\Leads\Filament\Resources\Leads\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Zuber\Kit\Leads\Actions\MarkLeadProcessedAction;
use Zuber\Kit\Leads\Enums\LeadStatusEnum;
use Zuber\Kit\Leads\Filament\Resources\Leads\LeadResource;
use Zuber\Kit\Leads\Models\Lead;
use Zuber\Kit\Support\PhoneSupport;

final class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Получена'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('Клиент'))
                    ->description(fn (Lead $record): ?string => PhoneSupport::format($record->phone))
                    ->searchable(['name', 'phone']),
                TextColumn::make('message')
                    ->label(__('Комментарий'))
                    ->limit(80)
                    ->tooltip(fn (Lead $record): ?string => $record->message)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('source')
                    ->label(__('Форма'))
                    ->description(fn (Lead $record): ?string => $record->url === null ? null : (string) parse_url($record->url, PHP_URL_PATH))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('Статус'))
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('Статус'))->options(LeadStatusEnum::class),
            ])
            ->recordActions([
                Action::make('call')
                    ->label(__('Позвонить'))
                    ->icon(Heroicon::OutlinedPhone)
                    ->color('gray')
                    ->url(fn (Lead $record): string => 'tel:' . $record->phone),
                Action::make('markProcessed')
                    ->label(__('Обработана'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Lead $record): bool => $record->status === LeadStatusEnum::NEW && LeadResource::canEdit($record))
                    ->action(function (Lead $record, MarkLeadProcessedAction $markProcessed): void {
                        $markProcessed->handle($record);
                        Notification::make()->success()->title(__('Заявка обработана'))->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
