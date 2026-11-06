<?php

namespace Zuber\Kit\Modules\Users\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;
use Zuber\Kit\Modules\Users\Exceptions\RoleException;
use Zuber\Kit\Modules\Users\Exceptions\SuperAdminRoleException;
use Zuber\Kit\Modules\Users\Repositories\RoleRepository;

/**
 * Кто видит разделы «Пользователи» и «Роли» и что в них может. Супер-администратор (разработчик)
 * виден и управляется только другими супер-администраторами; его права не настраиваются.
 */
final readonly class UserAccessService
{
    public function __construct(
        private UsersSettingsData $settings,
        private RoleRepository $roles,
    ) {}

    public function isSuperAdmin(HasRolesInterface $user): bool
    {
        return $this->settings->superAdminRole !== null && $user->hasRole($this->settings->superAdminRole);
    }

    public function canManage(HasRolesInterface $actor): bool
    {
        if ($this->isSuperAdmin($actor) || $this->settings->managePermission === null) {
            return true;
        }

        return $actor->checkPermissionTo($this->settings->managePermission);
    }

    public function canSee(HasRolesInterface $actor, HasRolesInterface $user): bool
    {
        return $this->canManage($actor) && (! $this->isSuperAdmin($user) || $this->isSuperAdmin($actor));
    }

    /**
     * Себя удалить нельзя: так администратор не останется без доступа по ошибке.
     */
    public function canDelete(HasRolesInterface $actor, HasRolesInterface $user): bool
    {
        return $this->canSee($actor, $user) && ! $this->isSameUser($actor, $user);
    }

    /**
     * Роль, пользователей с которой $actor не видит.
     */
    public function hiddenRole(HasRolesInterface $actor): ?string
    {
        return $this->isSuperAdmin($actor) ? null : $this->settings->superAdminRole;
    }

    /**
     * Роли, которые $actor может выдать: имя => подпись.
     *
     * @return array<string, string>
     */
    public function assignableRoles(HasRolesInterface $actor): array
    {
        $hiddenRole = $this->hiddenRole($actor);

        return collect($this->roles->names($this->hiddenRole($actor)))
            ->mapWithKeys(fn (string $name): array => [$name => $this->settings->roleLabel($name)])
            ->all();
    }

    public function isSuperAdminRole(Role $role): bool
    {
        return $role->name === $this->settings->superAdminRole;
    }

    public function canEditRole(HasRolesInterface $actor, Role $role): bool
    {
        return $this->canManage($actor) && ! $this->isSuperAdminRole($role);
    }

    /**
     * Роли из кода не удаляются: на них опираются код, сидер и kit:make-user.
     */
    public function canDeleteRole(HasRolesInterface $actor, Role $role): bool
    {
        return $this->canEditRole($actor, $role) && ! $this->settings->isCodeRole($role->name);
    }

    /**
     * Права, которых у $actor нет, он не выдаёт и не забирает — иначе выдал бы их себе через свою роль.
     *
     * @param array<int, string> $permissions        права роли после сохранения
     * @param array<int, string> $currentPermissions права роли до сохранения
     *
     * @throws RoleException
     */
    public function ensureCanChangePermissions(HasRolesInterface $actor, array $permissions, array $currentPermissions): void
    {
        if ($this->isSuperAdmin($actor)) {
            return;
        }

        $notHeld = collect($permissions)->diff($currentPermissions)
            ->merge(collect($currentPermissions)->diff($permissions))
            ->reject(fn (string $permission): bool => $actor->checkPermissionTo($permission))
            ->map(fn (string $permission): string => $this->settings->permissionLabel($permission))
            ->values()
            ->all();

        if ($notHeld !== []) {
            throw RoleException::permissionsNotHeld($notHeld);
        }
    }

    /**
     * @param array<int, string> $roles        роли после сохранения
     * @param array<int, string> $currentRoles роли до сохранения
     *
     * @throws SuperAdminRoleException
     */
    public function ensureCanAssign(HasRolesInterface $actor, array $roles, array $currentRoles): void
    {
        $superAdminRole = $this->hiddenRole($actor);

        if ($superAdminRole !== null && in_array($superAdminRole, $roles, true) !== in_array($superAdminRole, $currentRoles, true)) {
            throw SuperAdminRoleException::make();
        }
    }

    private function isSameUser(HasRolesInterface $actor, HasRolesInterface $user): bool
    {
        return $actor instanceof Model && $user instanceof Model && $actor->is($user);
    }
}
