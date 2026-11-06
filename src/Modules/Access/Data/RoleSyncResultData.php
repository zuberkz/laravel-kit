<?php

namespace Zuber\Kit\Modules\Access\Data;

/**
 * Итог kit:sync-roles. Ничего не удаляется: роли заводят в админке, у лишних прав могут быть роли.
 */
final readonly class RoleSyncResultData
{
    /**
     * @param array<int, string> $createdRoles     роли из enum, которых не было в базе
     * @param array<int, string> $stalePermissions права в базе, которых нет в коде
     */
    public function __construct(
        public int $roles,
        public int $permissions,
        public array $createdRoles,
        public array $stalePermissions,
    ) {}
}
