<?php

namespace Zuber\Kit\Modules\Pages\Support;

use Illuminate\Support\Facades\URL;
use Zuber\Kit\Modules\Pages\Models\Page;

/**
 * Ссылка на предпросмотр страницы (черновика тоже) — для админки и MCP: подписана на час, подпись по
 * пути без схемы и домена (маршрут — signed:relative), поэтому за прокси с https снаружи и http
 * внутри она та же.
 */
final class PagePreviewSupport
{
    public static function url(Page $page): string
    {
        return URL::to(URL::temporarySignedRoute('kit.pages.preview', now()->addHour(), ['page' => $page], absolute: false));
    }
}
