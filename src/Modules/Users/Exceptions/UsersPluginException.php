<?php

namespace Zuber\Kit\Modules\Users\Exceptions;

use RuntimeException;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;

/**
 * Разделы «Пользователи» и «Роли» подключены к проекту не до конца — ошибка настройки, а не отказ.
 */
final class UsersPluginException extends RuntimeException
{
    public static function guest(): self
    {
        return new self('The users section needs an authenticated panel user.');
    }

    public static function roleModel(string $model): self
    {
        return new self(sprintf('%s must be the role model of spatie/laravel-permission.', $model));
    }

    public static function userModelWithoutRoles(string $model): self
    {
        return new self(sprintf('%s must implement %s (use Spatie\Permission\Traits\HasRoles).', $model, HasRolesInterface::class));
    }
}
