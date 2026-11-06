<?php

namespace Zuber\Kit\Modules\Users\Actions;

use Illuminate\Database\ConnectionInterface;
use Spatie\Permission\Contracts\Role;
use Zuber\Kit\Modules\Users\Exceptions\RoleException;
use Zuber\Kit\Modules\Users\Services\RoleService;

/**
 * Удаление роли из раздела «Роли»: только роли, заведённой в админке и никому не выданной.
 */
final readonly class DeleteRoleAction
{
    public function __construct(
        private ConnectionInterface $db,
        private RoleService $roles,
    ) {}

    /**
     * @throws RoleException
     */
    public function handle(Role $role): void
    {
        $this->db->transaction(fn () => $this->roles->delete($role));
    }
}
