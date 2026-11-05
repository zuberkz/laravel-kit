<?php

namespace Zuber\Kit\Pages\Http\Controllers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Zuber\Kit\Pages\Services\PageRenderService;
use Zuber\Kit\Pages\Services\PageService;

/**
 * Опубликованная страница конструктора по адресу запроса; маршруты — Route::kitPages() в routes/web.php.
 */
final class PageController
{
    public function __invoke(Request $request, PageService $pages, PageRenderService $renderer, Repository $config): View
    {
        $page = $pages->findPublished($request->path());

        abort_if($page === null, 404);

        /** @var view-string $view */
        $view = 'kit::pages.show';

        return view($view, ['page' => $renderer->render($page), 'layout' => $config->get('kit.pages.layout')]);
    }
}
