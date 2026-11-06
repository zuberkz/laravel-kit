<?php

namespace Zuber\Kit\Modules\Users\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Data\RoleData;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;
use Zuber\Kit\Modules\Users\Exceptions\RoleException;
use Zuber\Kit\Modules\Users\Repositories\RoleRepository;

/**
 * Роли, которые заводят в админке: права — только из кода (PermissionEnum), роли из кода не
 * переименовываются и не удаляются, роль с пользователями не удаляется.
 */
final readonly class RoleService
{
    public function __construct(
        private RoleRepository $roles,
        private UserAccessService $access,
        private UsersSettingsData $settings,
    ) {}

    /**
     * @throws RoleException
     */
    public function create(RoleData $data, HasRolesInterface $actor): Model&Role
    {
        $this->ensureValid($data);
        $this->access->ensureCanChangePermissions($actor, $data->permissions, []);

        return $this->roles->create($data->name, $data->permissions);
    }

    /**
     * @throws RoleException
     */
    public function update(Model&Role $role, RoleData $data, HasRolesInterface $actor): Model&Role
    {
        if ($this->access->isSuperAdminRole($role)) {
            throw RoleException::superAdminRole();
        }

        if ($data->name !== $role->name && $this->settings->isCodeRole($role->name)) {
            throw RoleException::codeRole($this->settings->roleLabel($role->name));
        }

        $this->ensureValid($data, $role);
        $this->access->ensureCanChangePermissions($actor, $data->permissions, $this->knownPermissions($this->roles->permissionNames($role)));

        return $this->roles->update($role, $data->name, $data->permissions);
    }

    /**
     * @throws RoleException
     */
    public function delete(Role $role): void
    {
        if ($this->access->isSuperAdminRole($role)) {
            throw RoleException::superAdminRole();
        }

        $label = $this->settings->roleLabel($role->name);

        if ($this->settings->isCodeRole($role->name)) {
            throw RoleException::codeRole($label);
        }

        $users = $this->roles->usersCount($role);

        if ($users > 0) {
            throw RoleException::hasUsers($label, $users);
        }

        $this->roles->delete($role);
    }

    /**
     * @throws RoleException
     */
    private function ensureValid(RoleData $data, ?Role $role = null): void
    {
        if ($data->name === '') {
            throw RoleException::emptyName();
        }

        if ($this->roles->nameTaken($data->name, $role)) {
            throw RoleException::nameTaken($data->name);
        }

        $unknown = collect($data->permissions)->diff(array_keys($this->settings->permissionOptions()))->values()->all();

        if ($unknown !== []) {
            throw RoleException::unknownPermissions($unknown);
        }
    }

    /**
     * Права роли, которые есть в коде: устаревшие (например, от прежнего плагина) при сохранении
     * снимаются и в проверку «есть ли у вас это право» не попадают.
     *
     * @param  array<int, string> $permissions
     * @return array<int, string>
     */
    private function knownPermissions(array $permissions): array
    {
        return collect($permissions)->intersect(array_keys($this->settings->permissionOptions()))->values()->all();
    }
}
