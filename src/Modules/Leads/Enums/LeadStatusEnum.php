<?php

namespace Zuber\Kit\Modules\Leads\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadStatusEnum: string implements HasColor, HasLabel
{
    case NEW = 'new';
    case PROCESSED = 'processed';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => __('Новая'),
            self::PROCESSED => __('Обработана'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEW => 'warning',
            self::PROCESSED => 'gray',
        };
    }
}
