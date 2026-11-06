<?php

namespace Zuber\Kit\Modules\Users\Actions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Data\RoleData;
use Zuber\Kit\Modules\Users\Exceptions\RoleException;
use Zuber\Kit\Modules\Users\Services\RoleService;

/**
 * Новая роль из раздела «Роли»: роль и её права — одной транзакцией.
 */
final readonly class CreateRoleAction
{
    public function __construct(
        private ConnectionInterface $db,
        private RoleService $roles,
    ) {}

    /**
     * @throws RoleException
     */
    public function handle(RoleData $data, HasRolesInterface $actor): Model&Role
    {
        return $this->db->transaction(fn (): Model&Role => $this->roles->create($data, $actor));
    }
}
