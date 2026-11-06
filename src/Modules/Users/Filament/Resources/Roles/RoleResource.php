<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Roles;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;
use UnitEnum;
use Zuber\Kit\Core\Exceptions\DomainException;
use Zuber\Kit\Modules\Users\Actions\DeleteRoleAction;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages\CreateRole;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages\EditRole;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages\ListRoles;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Schemas\RoleForm;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Tables\RolesTable;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\UserResource;
use Zuber\Kit\Modules\Users\Repositories\RoleRepository;
use Zuber\Kit\Modules\Users\Services\UserAccessService;

/**
 * Раздел «Роли»: роли заводят и правят здесь, права ролям отмечают галочками из PermissionEnum
 * проекта. Роли из RoleEnum создаёт kit:sync-roles; их можно только перенастроить. Супер-
 * администратор не настраивается — у него всегда все права.
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

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    /**
     * @return Builder<Model&Role>
     */
    public static function getEloquentQuery(): Builder
    {
        $actor = UserResource::actor();

        return app(RoleRepository::class)->query($actor === null ? null : app(UserAccessService::class)->hiddenRole($actor));
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $actor = UserResource::actor();

        if ($actor === null) {
            return Response::deny();
        }

        $access = app(UserAccessService::class);
        $ability = match (true) {
            $action instanceof BackedEnum => (string) $action->value,
            $action instanceof UnitEnum => $action->name,
            default => $action,
        };

        $allowed = match ($ability) {
            'viewAny', 'create' => $access->canManage($actor),
            'view', 'update' => $record instanceof Role && $access->canEditRole($actor, $record),
            'delete' => $record instanceof Role && $access->canDeleteRole($actor, $record),
            default => false,
        };

        return $allowed ? Response::allow() : Response::deny();
    }

    /**
     * Удаление через DeleteRoleAction: роль с пользователями не удаляется, отказ — уведомлением.
     */
    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()->using(function (Model $record): bool {
            if (! $record instanceof Role) {
                return false;
            }

            try {
                app(DeleteRoleAction::class)->handle($record);
            } catch (DomainException $exception) {
                report($exception);
                Notification::make()->danger()->title($exception->getMessage())->send();

                // Отказ закрывает окно подтверждения: повторное «Удалить» ничего не изменит
                throw new Cancel;
            }

            return true;
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
