<?php

namespace Zuber\Kit\Modules\Pages;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Zuber\Kit\Modules\Pages\Http\Controllers\PageController;
use Zuber\Kit\Modules\Pages\Seo\PagesSitemapSource;
use Zuber\Kit\Modules\Seo\Contracts\SitemapSourceInterface;

/**
 * Конструктор страниц, если проект его включил (kit.pages.enabled): шаблоны блоков проекта,
 * маршруты (Route::kitPages(), предпросмотр), страницы в sitemap, миграции.
 */
final class PagesServiceProvider extends ServiceProvider
{
    public function boot(Repository $config): void
    {
        Blade::componentNamespace('Zuber\\Kit\\Modules\\Pages\\View\\Components', 'kit');

        if ($this->app->runningInConsole()) {
            $migrations = __DIR__ . '/../../../database/migrations';

            $this->publishesMigrations([
                $migrations . '/2026_11_05_000001_create_pages_table.php' => database_path('migrations/2026_11_05_000001_create_pages_table.php'),
                $migrations . '/2026_11_05_000002_create_shared_blocks_table.php' => database_path('migrations/2026_11_05_000002_create_shared_blocks_table.php'),
            ], 'kit-pages-migrations');
        }

        if ($config->get('kit.pages.enabled') !== true) {
            return;
        }

        $views = $config->get('kit.pages.views');
        $views = is_string($views) ? $views : $this->app->resourcePath('views/blocks');

        if (is_dir($views)) {
            Blade::anonymousComponentPath($views, 'blocks');
        }

        $this->app->tag([PagesSitemapSource::class], SitemapSourceInterface::TAG);

        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../../../routes/pages.php');
        }

        // Страницы конструктора: главная (имя home) и fallback — он проверяется после всех маршрутов
        // сайта, где бы ни стоял Route::kitPages() в routes/web.php
        Route::macro('kitPages', function (): void {
            Route::get('/', PageController::class)->name('home');
            Route::fallback(PageController::class)->name('kit.page');
        });
    }
}
