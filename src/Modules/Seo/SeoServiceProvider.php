<?php

namespace Zuber\Kit\Modules\Seo;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;
use Zuber\Kit\Modules\Seo\Contracts\SitemapSourceInterface;
use Zuber\Kit\Modules\Seo\Http\Middleware\NoIndexOutsideProductionMiddleware;
use Zuber\Kit\Modules\Seo\Services\SitemapService;

/**
 * robots.txt, sitemap.xml и llms.txt из разделов с тегом SitemapSourceInterface::TAG, noindex вне прода.
 */
final class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(SitemapService::class)
            ->needs('$sources')
            ->giveTagged(SitemapSourceInterface::TAG);
    }

    public function boot(Repository $config): void
    {
        $this->app->make(HttpKernel::class)->pushMiddleware(NoIndexOutsideProductionMiddleware::class);

        if ($config->get('kit.seo.routes') === true && ! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../../../routes/seo.php');
        }
    }
}
