<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Services\PageService;

#[IsReadOnly]
final class ListPagesTool extends PagesTool
{
    protected string $name = 'list-pages';

    protected string $description = 'Страницы сайта из конструктора: id, название, адрес, статус (draft — черновик, published — на сайте), ссылка. Блоки страницы — get-page.';

    public function __construct(private readonly PageService $pages) {}

    protected function respond(Request $request): Response
    {
        return Response::json([
            'pages' => $this->pages->list()->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'path' => $page->path,
                'status' => $page->status->value,
                'url' => url($page->path),
                'noindex' => $page->noindex,
                'updated_at' => $page->updated_at?->toIso8601String(),
            ])->all(),
        ]);
    }
}
