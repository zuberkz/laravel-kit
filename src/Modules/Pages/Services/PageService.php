<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Repositories\PageRepository;

/**
 * Страницы конструктора: запись и копирование; связи с общими блоками — по блокам страницы.
 */
final readonly class PageService
{
    public function __construct(private PageRepository $pages) {}

    public function findPublished(string $path): ?Page
    {
        return $this->pages->findPublished(PageData::normalizePath($path));
    }

    public function create(PageData $data): Page
    {
        return $this->pages->create($data->attributes(), PageRenderService::sharedIds($data->blocks));
    }

    public function update(Page $page, PageData $data): Page
    {
        return $this->pages->update($page, $data->attributes(), PageRenderService::sharedIds($data->blocks));
    }

    /**
     * Копия — черновик с теми же блоками и свободным адресом: /about-copy, /about-copy-2…
     */
    public function duplicate(Page $page): Page
    {
        $data = new PageData(
            title: __(':title (копия)', ['title' => $page->title]),
            path: $this->freeCopyPath($page->path),
            status: PageStatusEnum::DRAFT,
            blocks: $page->blocks ?? [],
            showHeader: $page->show_header,
            showFooter: $page->show_footer,
            seoTitle: $page->seo_title,
            seoDescription: $page->seo_description,
            ogImage: $page->og_image,
            noindex: $page->noindex,
        );

        return $this->create($data);
    }

    private function freeCopyPath(string $path): string
    {
        $base = rtrim($path === Page::HOME_PATH ? '/home' : $path, '/') . '-copy';
        $candidate = $base;
        $number = 2;

        while ($this->pages->pathExists($candidate)) {
            $candidate = $base . '-' . $number++;
        }

        return $candidate;
    }
}
