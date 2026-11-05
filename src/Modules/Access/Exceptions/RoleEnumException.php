<?php

namespace Zuber\Kit\Modules\Access\Exceptions;

use RuntimeException;
use Zuber\Kit\Modules\Access\Contracts\RoleEnumInterface;

/**
 * kit.access.roles указывает не на enum ролей — ошибка настройки проекта.
 */
final class RoleEnumException extends RuntimeException
{
    public static function invalid(string $class): self
    {
        return new self(sprintf('%s must be a backed enum implementing %s.', $class, RoleEnumInterface::class));
    }
}
