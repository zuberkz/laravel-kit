<?php

namespace Zuber\Kit\Pages\Data;

use Zuber\Kit\Pages\Blocks\Block;
use Zuber\Kit\Pages\Enums\BlockBackgroundEnum;
use Zuber\Kit\Pages\Enums\BlockSpacingEnum;

/**
 * Блок, готовый к выводу: шаблон, объект с данными, якорь для ссылок из меню, фон и отступы.
 */
final readonly class RenderedBlockData
{
    public function __construct(
        public string $view,
        public Block $block,
        public BlockBackgroundEnum $background,
        public BlockSpacingEnum $paddingTop,
        public BlockSpacingEnum $paddingBottom,
        public ?string $anchor = null,
    ) {}

    /**
     * Классы обёртки блока: их значения — в теме проекта (resources/css/blocks.css).
     */
    public function classes(): string
    {
        return implode(' ', [$this->background->cssClass(), $this->paddingTop->topClass(), $this->paddingBottom->bottomClass()]);
    }
}
