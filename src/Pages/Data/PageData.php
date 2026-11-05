<?php

namespace Zuber\Kit\Pages\Data;

use Illuminate\Support\Arr;
use Zuber\Kit\Pages\Enums\PageStatusEnum;

/**
 * Страница из формы админки.
 */
final readonly class PageData
{
    /**
     * @param array<int|string, mixed> $blocks блоки конструктора Filament: [uuid => {type, data}]
     */
    public function __construct(
        public string $title,
        public string $path,
        public PageStatusEnum $status,
        public array $blocks,
        public bool $showHeader = true,
        public bool $showFooter = true,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $ogImage = null,
        public bool $noindex = false,
    ) {}

    /**
     * @param array<string, mixed> $state
     */
    public static function fromForm(array $state): self
    {
        $status = $state['status'] ?? null;

        return new self(
            title: Arr::string($state, 'title'),
            path: self::normalizePath(Arr::string($state, 'path')),
            status: $status instanceof PageStatusEnum ? $status : PageStatusEnum::from(is_string($status) ? $status : 'draft'),
            blocks: is_array($state['blocks'] ?? null) ? array_values($state['blocks']) : [],
            showHeader: (bool) ($state['show_header'] ?? true),
            showFooter: (bool) ($state['show_footer'] ?? true),
            seoTitle: self::text($state['seo_title'] ?? null),
            seoDescription: self::text($state['seo_description'] ?? null),
            ogImage: self::text($state['og_image'] ?? null),
            noindex: (bool) ($state['noindex'] ?? false),
        );
    }

    /**
     * Адрес с одним ведущим слешем, без завершающего: «services/cleaning/» → «/services/cleaning».
     */
    public static function normalizePath(string $path): string
    {
        return '/' . trim(mb_strtolower(trim($path)), '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'title' => $this->title,
            'path' => $this->path,
            'status' => $this->status,
            'blocks' => $this->blocks,
            'show_header' => $this->showHeader,
            'show_footer' => $this->showFooter,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'og_image' => $this->ogImage,
            'noindex' => $this->noindex,
        ];
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
