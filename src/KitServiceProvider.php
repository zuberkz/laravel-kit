<?php

namespace Zuber\Kit;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Zuber\Kit\Access\Commands\SyncRolesCommand;
use Zuber\Kit\Http\Middleware\SecurityHeadersMiddleware;
use Zuber\Kit\Leads\Livewire\LeadForm;
use Zuber\Kit\Pages\Http\Controllers\PageController;
use Zuber\Kit\Pages\Seo\PagesSitemapSource;
use Zuber\Kit\Seo\Contracts\SitemapSourceInterface;
use Zuber\Kit\Seo\Http\Middleware\NoIndexOutsideProductionMiddleware;
use Zuber\Kit\Seo\Services\SitemapService;

/**
 * Что кит даёт проекту сам: настройки kit.*, канал лога domain, заголовки безопасности и noindex
 * вне прода на каждый ответ, robots.txt и sitemap.xml, команда kit:sync-roles, а если проект их
 * включил (kit.pages.enabled, kit.leads.enabled) — конструктор страниц и форму заявки. Файлы проект
 * забирает через vendor:publish — каждая группа своим тегом, выбор в post-update-cmd.
 */
final class KitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Вложенные ключи проекта поверх кита: config/kit.php проекта может задать только pages.enabled
        $this->app->booting(function (): void {
            $config = $this->app->make(Repository::class);
            $config->set('kit', array_replace_recursive(require __DIR__ . '/../config/kit.php', (array) $config->get('kit', [])));
        });
        $this->mergeConfigFrom(__DIR__ . '/../config/logging-channels.php', 'logging.channels');

        $this->app->when(SitemapService::class)
            ->needs('$sources')
            ->giveTagged(SitemapSourceInterface::TAG);
    }

    public function boot(Repository $config): void
    {
        // Ядро — через контракт: по классу Kernel контейнер собрал бы новое, мимо ядра, которое обслуживает запросы
        $kernel = $this->app->make(HttpKernel::class);
        $kernel->pushMiddleware(SecurityHeadersMiddleware::class);
        $kernel->pushMiddleware(NoIndexOutsideProductionMiddleware::class);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'kit');
        Blade::componentNamespace('Zuber\\Kit\\Pages\\View\\Components', 'kit');
        Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components', 'kit');

        if ($config->get('kit.seo.routes') === true && ! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/seo.php');
        }

        if ($config->get('kit.pages.enabled') === true) {
            $this->bootPages($config);
        }

        if ($config->get('kit.leads.enabled') === true && class_exists(Livewire::class)) {
            Livewire::component('kit-lead-form', LeadForm::class);
        }

        if ($this->app->runningInConsole()) {
            $this->bootConsole();
        }
    }

    private function bootPages(Repository $config): void
    {
        $views = $config->get('kit.pages.views');
        $views = is_string($views) ? $views : $this->app->resourcePath('views/blocks');

        if (is_dir($views)) {
            Blade::anonymousComponentPath($views, 'blocks');
        }

        $this->app->tag([PagesSitemapSource::class], SitemapSourceInterface::TAG);

        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/pages.php');
        }

        // Страницы конструктора: главная (имя home) и fallback — он проверяется после всех маршрутов
        // сайта, где бы ни стоял Route::kitPages() в routes/web.php
        Route::macro('kitPages', function (): void {
            Route::get('/', PageController::class)->name('home');
            Route::fallback(PageController::class)->name('kit.page');
        });
    }

    private function bootConsole(): void
    {
        // Синхронизация ролей нужна только проектам со spatie/laravel-permission
        if (class_exists(PermissionRegistrar::class)) {
            $this->commands([SyncRolesCommand::class]);
        }

        $resources = __DIR__ . '/../resources';
        $migrations = __DIR__ . '/../database/migrations';

        $this->publishes([$resources . '/rules' => base_path('.claude/rules/kit')], 'kit-rules');
        $this->publishes([$resources . '/stubs/tests/Arch/KitArchTest.php' => base_path('tests/Arch/KitArchTest.php')], 'kit-arch');
        $this->publishes([$resources . '/stubs/pint.json' => base_path('pint.json')], 'kit-pint');
        $this->publishesMigrations([
            $migrations . '/2026_11_05_000001_create_pages_table.php' => database_path('migrations/2026_11_05_000001_create_pages_table.php'),
            $migrations . '/2026_11_05_000002_create_shared_blocks_table.php' => database_path('migrations/2026_11_05_000002_create_shared_blocks_table.php'),
        ], 'kit-pages-migrations');
        $this->publishesMigrations([
            $migrations . '/2026_11_05_000003_create_lead_forms_table.php' => database_path('migrations/2026_11_05_000003_create_lead_forms_table.php'),
            $migrations . '/2026_11_05_000004_create_leads_table.php' => database_path('migrations/2026_11_05_000004_create_leads_table.php'),
        ], 'kit-leads-migrations');
    }
}
