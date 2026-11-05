<?php

namespace Zuber\Kit\Modules\Users\Services;

use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;
use Zuber\Kit\Modules\Users\Exceptions\SuperAdminRoleException;

/**
 * Кто видит раздел «Пользователи» и что в нём может. Супер-администратор (разработчик) виден и
 * управляется только другими супер-администраторами.
 */
final readonly class UserAccessService
{
    public function __construct(private UsersSettingsData $settings) {}

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

        return collect($this->settings->roleLabels())
            ->reject(fn (string $label, string $name): bool => $name === $hiddenRole)
            ->all();
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
