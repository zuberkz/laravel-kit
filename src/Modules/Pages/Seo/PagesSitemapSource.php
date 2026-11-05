<?php

namespace Zuber\Kit\Modules\Pages\Seo;

use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Repositories\PageRepository;
use Zuber\Kit\Modules\Seo\Contracts\SitemapSourceInterface;
use Zuber\Kit\Modules\Seo\Data\SitemapUrlData;

/**
 * Опубликованные страницы конструктора в sitemap.xml (кроме закрытых от индексации).
 */
final readonly class PagesSitemapSource implements SitemapSourceInterface
{
    public function __construct(private PageRepository $pages) {}

    public function urls(): iterable
    {
        return $this->pages->publishedForSitemap()
            ->map(fn (Page $page): SitemapUrlData => new SitemapUrlData($page->path, $page->updated_at))
            ->all();
    }
}
