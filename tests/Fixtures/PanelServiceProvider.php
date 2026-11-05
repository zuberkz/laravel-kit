<?php

namespace Zuber\Kit\Tests\Fixtures;

use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Zuber\Kit\Modules\Leads\LeadsPlugin;
use Zuber\Kit\Modules\Pages\PagesPlugin;
use Zuber\Kit\Modules\Users\UsersPlugin;

final class PanelServiceProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('panel')
            ->path('panel')
            ->login()
            ->plugin(
                UsersPlugin::make(RoleEnum::class)
                    ->superAdminRole(RoleEnum::SUPER_ADMIN)
                    ->managePermission(PermissionEnum::MANAGE_USERS)
                    ->permissions(PermissionEnum::class),
            )
            ->plugin(PagesPlugin::make())
            ->plugin(LeadsPlugin::make())
            ->middleware([
                EncryptCookies::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
