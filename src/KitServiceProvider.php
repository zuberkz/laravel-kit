<?php

namespace Zuber\Kit;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Zuber\Kit\Access\Commands\SyncRolesCommand;
use Zuber\Kit\Http\Middleware\SecurityHeadersMiddleware;
use Zuber\Kit\Seo\Contracts\SitemapSourceInterface;
use Zuber\Kit\Seo\Http\Middleware\NoIndexOutsideProductionMiddleware;
use Zuber\Kit\Seo\Services\SitemapService;

/**
 * Что кит даёт проекту сам: настройки kit.*, канал лога domain, заголовки безопасности и noindex
 * вне прода на каждый ответ, robots.txt и sitemap.xml, команда kit:sync-roles. Файлы проект
 * забирает через vendor:publish — каждая группа своим тегом, выбор в post-update-cmd.
 */
final class KitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/kit.php', 'kit');
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

        if ($config->get('kit.seo.routes') === true && ! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/seo.php');
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        // Синхронизация ролей нужна только проектам со spatie/laravel-permission
        if (class_exists(PermissionRegistrar::class)) {
            $this->commands([SyncRolesCommand::class]);
        }

        $resources = __DIR__ . '/../resources';

        $this->publishes([$resources . '/rules' => base_path('.claude/rules/kit')], 'kit-rules');
        $this->publishes([$resources . '/stubs/tests/Arch/KitArchTest.php' => base_path('tests/Arch/KitArchTest.php')], 'kit-arch');
        $this->publishes([$resources . '/stubs/pint.json' => base_path('pint.json')], 'kit-pint');
    }
}
