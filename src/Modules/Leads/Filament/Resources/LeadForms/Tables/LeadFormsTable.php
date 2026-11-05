<?php

namespace Zuber\Kit\Modules\Leads\Filament\Resources\LeadForms\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zuber\Kit\Modules\Leads\Models\LeadForm;

final class LeadFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Форма'))
                    ->description(fn (LeadForm $record): string => trans_choice(':count поле|:count поля|:count полей', count($record->fields), ['count' => count($record->fields)]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('button_link')
                    ->label(__('Ссылка для кнопки'))
                    ->state(fn (LeadForm $record): string => '#form-' . $record->id)
                    ->tooltip(__('Впишите в ссылку кнопки любого блока: форма откроется во всплывающем окне'))
                    ->copyable()
                    ->copyMessage(__('Ссылка скопирована'))
                    ->fontFamily('mono'),
                TextColumn::make('leads_count')
                    ->label(__('Заявок'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Изменена'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(__('Заявки с этой формы останутся, блоки с ней покажут «Обратный звонок».')),
            ]);
    }
}
