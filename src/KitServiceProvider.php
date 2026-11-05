<?php

namespace Zuber\Kit;

use Illuminate\Support\ServiceProvider;

/**
 * Канал лога domain и файлы, которые проект забирает себе через vendor:publish.
 * Каждая группа — свой тег: проект выбирает в post-update-cmd, что из набора ему нужно.
 */
final class KitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/logging-channels.php', 'logging.channels');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $resources = __DIR__ . '/../resources';

        $this->publishes([$resources . '/rules' => base_path('.claude/rules/kit')], 'kit-rules');
        $this->publishes([$resources . '/stubs/tests/Arch/KitArchTest.php' => base_path('tests/Arch/KitArchTest.php')], 'kit-arch');
        $this->publishes([$resources . '/stubs/pint.json' => base_path('pint.json')], 'kit-pint');
    }
}
