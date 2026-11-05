<?php

namespace Zuber\Kit\Pages\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PageStatusEnum: string implements HasColor, HasLabel
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('Черновик'),
            self::PUBLISHED => __('Опубликована'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'success',
        };
    }
}
