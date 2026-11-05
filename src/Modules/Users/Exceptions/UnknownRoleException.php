<?php

namespace Zuber\Kit\Modules\Users\Exceptions;

use Zuber\Kit\Core\Exceptions\DomainException;

/**
 * Пользователю назначают роль, которой нет в базе: роли раскладывает kit:sync-roles из RoleEnum.
 */
final class UnknownRoleException extends DomainException
{
    /**
     * @param array<int, string> $roles
     */
    public static function roles(array $roles): self
    {
        return new self(__('Ролей :roles нет в базе — проверьте имя в RoleEnum или запустите kit:sync-roles.', ['roles' => implode(', ', $roles)]));
    }

    public static function none(): self
    {
        return new self(__('Новому пользователю нужна роль: --role=admin.'));
    }
}
