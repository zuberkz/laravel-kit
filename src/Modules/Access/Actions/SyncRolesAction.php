<?php

namespace Zuber\Kit\Modules\Access\Actions;

use BackedEnum;
use Illuminate\Database\ConnectionInterface;
use Zuber\Kit\Modules\Access\Contracts\RoleEnumInterface;
use Zuber\Kit\Modules\Access\Data\RoleSyncResultData;
use Zuber\Kit\Modules\Access\Services\RoleSyncService;

/**
 * Синхронизация ролей и прав с кодом одной транзакцией: при деплое и в локальном сидере.
 */
final readonly class SyncRolesAction
{
    public function __construct(
        private ConnectionInterface $db,
        private RoleSyncService $roles,
    ) {}

    /**
     * @param class-string<BackedEnum&RoleEnumInterface> $roles          RoleEnum: роли по умолчанию
     * @param class-string<BackedEnum>|null              $permissions    PermissionEnum: все права проекта
     * @param string|null                                $superAdminRole роль, которой всегда выдаются все права
     */
    public function handle(string $roles, ?string $permissions = null, ?string $superAdminRole = null): RoleSyncResultData
    {
        return $this->db->transaction(fn (): RoleSyncResultData => $this->roles->sync($roles, $permissions, $superAdminRole));
    }
}
