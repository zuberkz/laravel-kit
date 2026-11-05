<?php

namespace Zuber\Kit\Access\Data;

/**
 * Итог kit:sync-roles. Лишние роли в базе не удаляются — у них могут быть пользователи.
 */
final readonly class RoleSyncResultData
{
    /**
     * @param array<int, string> $staleRoles роли в базе, которых нет в enum
     */
    public function __construct(
        public int $roles,
        public int $permissions,
        public array $staleRoles,
    ) {}
}
