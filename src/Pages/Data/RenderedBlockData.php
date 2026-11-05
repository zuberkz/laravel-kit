<?php

namespace Zuber\Kit\Pages\Data;

use Zuber\Kit\Pages\Blocks\Block;

/**
 * Блок, готовый к выводу: шаблон, объект с данными и якорь для ссылок из меню.
 */
final readonly class RenderedBlockData
{
    public function __construct(
        public string $view,
        public Block $block,
        public ?string $anchor = null,
    ) {}
}
