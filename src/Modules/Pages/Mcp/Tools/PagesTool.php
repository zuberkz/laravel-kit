<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Zuber\Kit\Core\Exceptions\DomainException;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Support\PagePreviewSupport;

/**
 * Инструмент конструктора страниц: отказ по правилу (DomainException) возвращается ошибкой с
 * сообщением — модель видит, что исправить; страница в ответах описана одинаково.
 */
abstract class PagesTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            return $this->respond($request);
        } catch (DomainException $exception) {
            return Response::error($exception->getMessage());
        }
    }

    abstract protected function respond(Request $request): Response|ResponseFactory;

    /**
     * Страница с блоками по номерам (index — для add-block, update-block, move-block, delete-block).
     *
     * @return array<string, mixed>
     */
    protected function page(Page $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'path' => $page->path,
            'status' => $page->status->value,
            'url' => url($page->path),
            'preview_url' => $page->isPublished() ? null : PagePreviewSupport::url($page),
            'show_header' => $page->show_header,
            'show_footer' => $page->show_footer,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'noindex' => $page->noindex,
            'blocks' => collect($page->blocks ?? [])
                ->values()
                ->map(fn (mixed $block, int $index): array => ['index' => $index, ...(is_array($block) ? $block : [])])
                ->all(),
        ];
    }
}
