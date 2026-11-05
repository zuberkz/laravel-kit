<?php

namespace Zuber\Kit\Modules\Pages\Data;

/**
 * Страница для шаблона: шапка, содержимое и подвал — уже собранные блоки, плюс мета-теги.
 */
final readonly class PageViewData
{
    /**
     * @param array<int, RenderedBlockData> $header
     * @param array<int, RenderedBlockData> $main
     * @param array<int, RenderedBlockData> $footer
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $ogImage,
        public bool $noindex,
        public array $header,
        public array $main,
        public array $footer,
    ) {}
}
