<?php

namespace Zuber\Kit\Modules\Pages\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Отступ блока сверху или снизу; размеры — в теме проекта (классы block-pt-*, block-pb-*).
 */
enum BlockSpacingEnum: string implements HasLabel
{
    case NONE = 'none';
    case SMALL = 'sm';
    case MEDIUM = 'md';
    case LARGE = 'lg';

    public function getLabel(): string
    {
        return match ($this) {
            self::NONE => __('Без отступа'),
            self::SMALL => __('Маленький'),
            self::MEDIUM => __('Обычный'),
            self::LARGE => __('Большой'),
        };
    }

    public function topClass(): string
    {
        return 'block-pt-' . $this->value;
    }

    public function bottomClass(): string
    {
        return 'block-pb-' . $this->value;
    }
}
