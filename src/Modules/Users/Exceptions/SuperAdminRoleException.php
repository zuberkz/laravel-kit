<?php

namespace Zuber\Kit\Modules\Users\Exceptions;

use Zuber\Kit\Core\Exceptions\DomainException;

/**
 * Роль супер-администратора выдаёт и снимает только супер-администратор.
 */
final class SuperAdminRoleException extends DomainException
{
    public static function make(): self
    {
        return new self(__('Роль супер-администратора может выдать только супер-администратор.'));
    }
}
