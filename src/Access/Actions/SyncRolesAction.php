<?php

namespace Zuber\Kit\Access\Actions;

use BackedEnum;
use Illuminate\Database\ConnectionInterface;
use Zuber\Kit\Access\Contracts\RoleEnumInterface;
use Zuber\Kit\Access\Data\RoleSyncResultData;
use Zuber\Kit\Access\Services\RoleSyncService;

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
