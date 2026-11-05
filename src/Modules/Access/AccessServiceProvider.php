<?php

namespace Zuber\Kit\Modules\Access;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Zuber\Kit\Modules\Access\Commands\SyncRolesCommand;

/**
 * kit:sync-roles — только в проектах со spatie/laravel-permission.
 */
final class AccessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole() && class_exists(PermissionRegistrar::class)) {
            $this->commands([SyncRolesCommand::class]);
        }
    }
}
