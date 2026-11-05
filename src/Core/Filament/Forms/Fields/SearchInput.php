<?php

namespace Zuber\Kit\Core\Filament\Forms\Fields;

use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

/**
 * Строка поиска: без подписи, с лупой, ищет на ходу с паузой, чтобы не дёргать сервер на каждый символ.
 */
final class SearchInput
{
    public static function make(string $name = 'search'): TextInput
    {
        return TextInput::make($name)
            ->hiddenLabel()
            ->type('search')
            ->placeholder(__('Поиск'))
            ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
            ->autocomplete('off')
            ->maxLength(100)
            ->live(debounce: 400);
    }
}
