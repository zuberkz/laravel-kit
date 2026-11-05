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
use Illuminate\Contracts\View\View;
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
                    ->description(fn (Lead $record): ?string => PhoneSupport::format($record->phone) ?? $record->email)
                    ->placeholder('—')
                    ->searchable(['name', 'phone', 'email']),
                TextColumn::make('answers')
                    ->label(__('Ответы'))
                    ->state(fn (Lead $record): string => self::summary($record))
                    ->limit(80)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('leadForm.name')
                    ->label(__('Форма'))
                    ->description(fn (Lead $record): ?string => $record->source)
                    ->placeholder(__('Обратный звонок'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('Статус'))
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label(__('Статус'))->options(LeadStatusEnum::class),
                SelectFilter::make('lead_form_id')->label(__('Форма'))->relationship('leadForm', 'name'),
            ])
            ->recordActions([
                Action::make('answers')
                    ->label(__('Ответы'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading(fn (Lead $record): string => __('Заявка от :date', ['date' => $record->created_at?->format('d.m.Y H:i')]))
                    ->modalContent(fn (Lead $record): View => self::answers($record))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Закрыть')),
                Action::make('call')
                    ->label(__('Позвонить'))
                    ->icon(Heroicon::OutlinedPhone)
                    ->color('gray')
                    ->visible(fn (Lead $record): bool => $record->phone !== null)
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

    /**
     * Ответы одной строкой, кроме контактов — они в колонке «Клиент».
     */
    private static function summary(Lead $record): string
    {
        return collect($record->answers)
            ->reject(fn (array $answer): bool => in_array($answer['value'], [$record->name, PhoneSupport::format($record->phone), $record->email], true))
            ->map(fn (array $answer): string => $answer['label'] . ': ' . $answer['value'])
            ->implode(' · ');
    }

    private static function answers(Lead $record): View
    {
        /** @var view-string $view */
        $view = 'kit::leads.answers';

        return view($view, ['lead' => $record]);
    }
}
