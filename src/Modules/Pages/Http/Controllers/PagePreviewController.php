<?php

namespace Zuber\Kit\Modules\Pages\Http\Controllers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Services\PageRenderService;

/**
 * Предпросмотр страницы из админки, черновика тоже: подписанная ссылка на время, без индексации.
 */
final class PagePreviewController
{
    public function __invoke(Page $page, PageRenderService $renderer, Repository $config): View
    {
        /** @var view-string $view */
        $view = 'kit::pages.show';

        return view($view, ['page' => $renderer->render($page, preview: true), 'layout' => $config->get('kit.pages.layout')]);
    }
}
