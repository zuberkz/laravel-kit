<?php

namespace Zuber\Kit\Modules\Spam;

use Filament\Forms\Components\TextInput;

/**
 * Поле-ловушка для ботов: человек его не видит и не попадает в него с клавиатуры, бот заполняет.
 * В данные формы не попадает (dehydrated(false)), проверяет UsesSpamProtection.
 */
final class HoneypotField
{
    public const string NAME = 'website';

    public static function make(): TextInput
    {
        return TextInput::make(self::NAME)
            ->hiddenLabel()
            ->dehydrated(false)
            ->autocomplete('off')
            ->extraInputAttributes(['tabindex' => '-1'])
            ->extraFieldWrapperAttributes([
                'aria-hidden' => 'true',
                'style' => 'position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden',
            ]);
    }
}
