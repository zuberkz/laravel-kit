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
     * @param class-string<BackedEnum&RoleEnumInterface> $roles
     */
    public function handle(string $roles): RoleSyncResultData
    {
        return $this->db->transaction(fn (): RoleSyncResultData => $this->roles->sync($roles));
    }
}
