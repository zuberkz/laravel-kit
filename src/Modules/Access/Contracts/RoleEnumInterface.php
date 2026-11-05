<?php

namespace Zuber\Kit\Modules\Access\Contracts;

use BackedEnum;

/**
 * Enum ролей проекта: права каждой роли задаются в коде, kit:sync-roles переносит их в базу.
 */
interface RoleEnumInterface
{
    /**
     * @return array<int, BackedEnum>
     */
    public function permissions(): array;
}
