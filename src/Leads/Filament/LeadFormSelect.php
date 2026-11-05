<?php

namespace Zuber\Kit\Leads\Filament;

use Filament\Forms\Components\Select;
use Zuber\Kit\Leads\Repositories\LeadFormRepository;

/**
 * Выбор формы заявки в блоке: LeadFormSelect::make('form'); пусто — «Обратный звонок».
 */
final class LeadFormSelect
{
    public static function make(string $name = 'form'): Select
    {
        return Select::make($name)
            ->label(__('Форма'))
            ->options(fn (): array => app(LeadFormRepository::class)->options())
            ->placeholder(__('Обратный звонок: имя, телефон, комментарий'))
            ->helperText(__('Формы собираются в разделе «Формы заявок»'))
            ->searchable()
            ->native(false);
    }
}
