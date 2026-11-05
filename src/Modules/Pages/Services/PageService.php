<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Repositories\PageRepository;
use Zuber\Kit\Modules\Pages\Support\PagePathSupport;

/**
 * Страницы конструктора: запись и копирование; связи с общими блоками — по блокам страницы.
 */
final readonly class PageService
{
    public function __construct(
        private PageRepository $pages,
        private Router $router,
    ) {}

    /**
     * @return Collection<int, Page>
     */
    public function list(): Collection
    {
        return $this->pages->list();
    }

    /**
     * @throws PageBuilderException
     */
    public function findOrFail(int $id): Page
    {
        return $this->pages->find($id) ?? throw PageBuilderException::pageNotFound($id);
    }

    /**
     * Адрес можно отдать странице: формат, не занят другой страницей и разделом сайта (в админке то
     * же проверяет форма).
     *
     * @throws PageBuilderException
     */
    public function assertPathAvailable(string $path, ?Page $page = null): void
    {
        match (true) {
            preg_match(PagePathSupport::PATTERN, $path) !== 1 => throw PageBuilderException::invalidPath($path),
            $this->pages->pathExists($path, $page?->id) => throw PageBuilderException::pathTaken($path),
            PagePathSupport::isTakenByRoute($this->router, $path) => throw PageBuilderException::pathTakenByRoute($path),
            default => null,
        };
    }

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
