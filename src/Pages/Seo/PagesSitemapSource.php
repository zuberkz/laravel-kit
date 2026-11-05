<?php

namespace Zuber\Kit\Pages\Seo;

use Zuber\Kit\Pages\Models\Page;
use Zuber\Kit\Pages\Repositories\PageRepository;
use Zuber\Kit\Seo\Contracts\SitemapSourceInterface;
use Zuber\Kit\Seo\Data\SitemapUrlData;

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
