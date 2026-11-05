<?php

namespace Zuber\Kit\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Zuber\Kit\Access\Actions\SyncRolesAction;
use Zuber\Kit\KitServiceProvider;
use Zuber\Kit\Tests\Fixtures\Blocks\FaqBlock;
use Zuber\Kit\Tests\Fixtures\Blocks\HeroBlock;
use Zuber\Kit\Tests\Fixtures\PanelServiceProvider;
use Zuber\Kit\Tests\Fixtures\RoleEnum;
use Zuber\Kit\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Провайдеры пакетов, которые в проекте подключает discovery.
     *
     * @param  Application              $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            LivewireServiceProvider::class,
            PowerJoinsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            PermissionServiceProvider::class,
            KitServiceProvider::class,
            PanelServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('database.default', 'testing');
        $app['config']->set('kit.access.roles', RoleEnum::class);
        $app['config']->set('kit.pages', [
            'enabled' => true,
            'blocks' => [HeroBlock::class, FaqBlock::class],
            'views' => __DIR__ . '/Fixtures/views/blocks',
            'layout' => 'test-layout',
        ]);
        $app['config']->set('kit.leads', ['enabled' => true, 'mail_to' => ['team@example.kz']]);
    }

    /**
     * Страницы конструктора — последним маршрутом, как в routes/web.php проекта.
     *
     * @param Router $router
     */
    protected function defineRoutes($router): void
    {
        Blade::anonymousComponentPath(__DIR__ . '/Fixtures/views/components');
        $router->kitPages();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Роли и права из enum — как kit:sync-roles при деплое.
     */
    protected function seedRoles(): void
    {
        $this->app->make(SyncRolesAction::class)->handle(RoleEnum::class);
    }

    protected function userWithRole(?RoleEnum $role, string $email = ''): User
    {
        $user = User::query()->create([
            'name' => $role === null ? 'Без роли' : $role->getLabel(),
            'email' => $email !== '' ? $email : fake()->unique()->safeEmail(),
            'password' => 'secret-password',
        ]);

        return $role === null ? $user : $user->assignRole($role);
    }
}
