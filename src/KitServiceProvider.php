<?php

namespace Zuber\Kit;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Zuber\Kit\Modules\Access\AccessServiceProvider;
use Zuber\Kit\Modules\Leads\LeadsServiceProvider;
use Zuber\Kit\Modules\Pages\PagesServiceProvider;
use Zuber\Kit\Modules\Security\SecurityServiceProvider;
use Zuber\Kit\Modules\Seo\SeoServiceProvider;
use Zuber\Kit\Modules\Users\UsersServiceProvider;

/**
 * Кит целиком: настройки kit.*, канал лога domain, шаблоны кита, файлы для проекта (vendor:publish,
 * каждая группа своим тегом) и модули из src/Modules — у каждого свой сервис-провайдер.
 */
final class KitServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string<ServiceProvider>>
     */
    private const array MODULES = [
        SecurityServiceProvider::class,
        SeoServiceProvider::class,
        AccessServiceProvider::class,
        UsersServiceProvider::class,
        PagesServiceProvider::class,
        LeadsServiceProvider::class,
    ];

    public function register(): void
    {
        // Вложенные ключи проекта поверх кита: config/kit.php проекта может задать только pages.enabled
        $this->app->booting(function (): void {
            $config = $this->app->make(Repository::class);
            $config->set('kit', array_replace_recursive(require __DIR__ . '/../config/kit.php', (array) $config->get('kit', [])));
        });
        $this->mergeConfigFrom(__DIR__ . '/../config/logging-channels.php', 'logging.channels');

        foreach (self::MODULES as $module) {
            $this->app->register($module);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'kit');
        Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components', 'kit');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $resources = __DIR__ . '/../resources';

        $this->publishes([$resources . '/rules' => base_path('.claude/rules/kit')], 'kit-rules');
        $this->publishes([$resources . '/stubs/tests/Arch/KitArchTest.php' => base_path('tests/Arch/KitArchTest.php')], 'kit-arch');
        $this->publishes([$resources . '/stubs/pint.json' => base_path('pint.json')], 'kit-pint');
    }
}
