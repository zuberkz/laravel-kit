<?php

namespace Zuber\Kit\Filament\Forms\Fields;

use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Zuber\Kit\Support\PhoneSupport;

/**
 * Телефон +7 с маской: на экране +7 (707) 123-45-67, в данные формы — +77071234567.
 */
final class PhoneInput
{
    public static function make(string $name = 'phone'): TextInput
    {
        return TextInput::make($name)
            ->label(__('Телефон'))
            // tel() добавил бы свою regex-проверку, которая не пускает скобки маски
            ->type('tel')
            ->inputMode('tel')
            ->mask('+7 (999) 999-99-99')
            ->placeholder('+7 (___) ___-__-__')
            ->prefixIcon(Heroicon::OutlinedPhone)
            ->rule('regex:/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/')
            ->validationMessages(['regex' => __('Введите номер полностью: +7 (707) 123-45-67.')])
            ->formatStateUsing(fn (?string $state): ?string => PhoneSupport::format($state))
            ->dehydrateStateUsing(fn (?string $state): ?string => PhoneSupport::normalize($state));
    }
}
