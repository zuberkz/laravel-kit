<?php

namespace Zuber\Kit\Modules\Pages\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Общий блок, который сам стоит на всех страницах: шапка — над содержимым, подвал — под ним.
 */
enum SharedBlockPositionEnum: string implements HasLabel
{
    case HEADER = 'header';
    case FOOTER = 'footer';

    public function getLabel(): string
    {
        return match ($this) {
            self::HEADER => __('Шапка'),
            self::FOOTER => __('Подвал'),
        };
    }
}
