<?php

namespace Zuber\Kit\Modules\Users;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Zuber\Kit\Modules\Users\Commands\MakeUserCommand;

final class UsersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole() && class_exists(PermissionRegistrar::class)) {
            $this->commands([MakeUserCommand::class]);
        }
    }
}
