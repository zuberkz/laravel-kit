<?php

namespace Zuber\Kit\Modules\Pages\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Фон блока по смыслу; как он выглядит, задаёт тема проекта классом block-bg-{value}
 * (resources/css/blocks.css): фирменный и тёмный фоны переопределяют токены цвета, и текст
 * блока перекрашивается сам.
 */
enum BlockBackgroundEnum: string implements HasLabel
{
    case PAGE = 'page';
    case SURFACE = 'surface';
    case BRAND = 'brand';
    case INVERSE = 'inverse';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAGE => __('Фон страницы'),
            self::SURFACE => __('Подложка'),
            self::BRAND => __('Фирменный цвет'),
            self::INVERSE => __('Тёмный'),
        };
    }

    public function cssClass(): string
    {
        return 'block-bg-' . $this->value;
    }
}
