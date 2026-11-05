<?php

namespace Zuber\Kit\Users\Filament\Resources\Roles;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;
use Zuber\Kit\Users\Data\UsersSettingsData;
use Zuber\Kit\Users\Filament\Resources\Roles\Pages\ListRoles;
use Zuber\Kit\Users\Filament\Resources\Roles\Tables\RolesTable;
use Zuber\Kit\Users\Filament\Resources\Users\UserResource;
use Zuber\Kit\Users\Repositories\RoleRepository;
use Zuber\Kit\Users\Services\UserAccessService;

/**
 * Раздел «Роли» — только просмотр: роли и их права задаются в коде проекта (RoleEnum::permissions())
 * и раскладываются в базу сидером, правка в админке разошлась бы с кодом.
 */
final class RoleResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 2;

    public static function getModel(): string
    {
        return app(RoleRepository::class)->modelClass();
    }

    public static function getModelLabel(): string
    {
        return __('роль');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Роли');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return app(UsersSettingsData::class)->navigationGroup;
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    /**
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        $actor = UserResource::actor();

        return app(RoleRepository::class)->query($actor === null ? null : app(UserAccessService::class)->hiddenRole($actor));
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $actor = UserResource::actor();

        return $action === 'viewAny' && $actor !== null && app(UserAccessService::class)->canManage($actor)
            ? Response::allow()
            : Response::deny();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
        ];
    }
}
