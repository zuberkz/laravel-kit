<?php

namespace Zuber\Kit\Modules\Seo\Http\Controllers;

use Illuminate\Http\Response;
use Zuber\Kit\Modules\Seo\Services\SitemapService;

final class SitemapController
{
    public function __invoke(SitemapService $sitemap): Response
    {
        return new Response($sitemap->xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
