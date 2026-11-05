<?php

namespace Zuber\Kit\Users\Filament\Resources\Users;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;
use Zuber\Kit\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Users\Data\UsersSettingsData;
use Zuber\Kit\Users\Exceptions\UsersPluginException;
use Zuber\Kit\Users\Filament\Resources\Users\Pages\CreateUser;
use Zuber\Kit\Users\Filament\Resources\Users\Pages\EditUser;
use Zuber\Kit\Users\Filament\Resources\Users\Pages\ListUsers;
use Zuber\Kit\Users\Filament\Resources\Users\Schemas\UserForm;
use Zuber\Kit\Users\Filament\Resources\Users\Tables\UsersTable;
use Zuber\Kit\Users\Repositories\UserRepository;
use Zuber\Kit\Users\Services\UserAccessService;

/**
 * Раздел «Пользователи». Доступ решает UserAccessService, а не политика модели: правила раздела
 * одинаковы во всех проектах.
 *
 * @extends resource<Model&HasRolesInterface>
 */
final class UserResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return app(UserRepository::class)->modelClass();
    }

    /**
     * В винительном падеже: Filament подставляет её в «Создать …», «Удалить …».
     */
    public static function getModelLabel(): string
    {
        return __('пользователя');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Пользователи');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return app(UsersSettingsData::class)->navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * @return Builder<Model&HasRolesInterface>
     */
    public static function getEloquentQuery(): Builder
    {
        $actor = self::actor();

        return app(UserRepository::class)->query($actor === null ? null : app(UserAccessService::class)->hiddenRole($actor));
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $actor = self::actor();

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
            'viewAny', 'create', 'deleteAny' => $access->canManage($actor),
            'view', 'update' => $record instanceof HasRolesInterface && $access->canSee($actor, $record),
            'delete' => $record instanceof HasRolesInterface && $access->canDelete($actor, $record),
            default => false,
        };

        return $allowed ? Response::allow() : Response::deny();
    }

    /**
     * Вошедший в панель пользователь.
     */
    public static function actor(): ?HasRolesInterface
    {
        $user = Filament::auth()->user();

        if ($user === null || $user instanceof HasRolesInterface) {
            return $user;
        }

        throw UsersPluginException::userModelWithoutRoles($user::class);
    }

    /**
     * Действие страницы без вошедшего пользователя не выполняется: панель пускает только вошедших.
     */
    public static function currentActor(): HasRolesInterface
    {
        return self::actor() ?? throw UsersPluginException::guest();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
