<?php

namespace Zuber\Kit\Modules\Users;

use BackedEnum;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Contracts\HasLabel;
use UnitEnum;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\RoleResource;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\UserResource;

/**
 * Разделы «Пользователи» и «Роли» в панели Filament:
 *
 *     ->plugin(UsersPlugin::make(RoleEnum::class)
 *         ->superAdminRole(RoleEnum::SUPER_ADMIN)
 *         ->managePermission(PermissionEnum::MANAGE_USERS)
 *         ->permissions(PermissionEnum::class)
 *         ->navigationGroup(NavigationGroupEnum::ACCESS))
 */
final class UsersPlugin implements Plugin
{
    private ?string $superAdminRole = null;

    private ?string $managePermission = null;

    /**
     * @var class-string<BackedEnum&HasLabel>|null
     */
    private ?string $permissions = null;

    private string|UnitEnum|null $navigationGroup = null;

    /**
     * @param class-string<BackedEnum&HasLabel> $roles
     */
    private function __construct(private readonly string $roles) {}

    /**
     * @param class-string<BackedEnum&HasLabel> $roles RoleEnum проекта
     */
    public static function make(string $roles): self
    {
        return new self($roles);
    }

    public function getId(): string
    {
        return 'kit-users';
    }

    public function superAdminRole(BackedEnum $role): self
    {
        $this->superAdminRole = (string) $role->value;

        return $this;
    }

    public function managePermission(BackedEnum $permission): self
    {
        $this->managePermission = (string) $permission->value;

        return $this;
    }

    /**
     * @param class-string<BackedEnum&HasLabel> $permissions PermissionEnum проекта
     */
    public function permissions(string $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function navigationGroup(string|UnitEnum|null $group): self
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function register(Panel $panel): void
    {
        app()->instance(UsersSettingsData::class, new UsersSettingsData(
            roles: $this->roles,
            superAdminRole: $this->superAdminRole,
            managePermission: $this->managePermission,
            permissions: $this->permissions,
            navigationGroup: $this->navigationGroup,
        ));

        $panel->resources([UserResource::class, RoleResource::class]);
    }

    public function boot(Panel $panel): void {}
}
