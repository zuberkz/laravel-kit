<?php

namespace Zuber\Kit;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Zuber\Kit\Access\Commands\SyncRolesCommand;

/**
 * Что кит даёт проекту сам: настройки kit.*, канал лога domain, команда kit:sync-roles. Файлы проект
 * забирает через vendor:publish — каждая группа своим тегом, выбор в post-update-cmd.
 */
final class KitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/kit.php', 'kit');
        $this->mergeConfigFrom(__DIR__ . '/../config/logging-channels.php', 'logging.channels');
    }

    public function boot(): void
    {
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
