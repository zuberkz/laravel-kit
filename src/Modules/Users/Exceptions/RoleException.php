<?php

namespace Zuber\Kit\Modules\Users\Exceptions;

use Zuber\Kit\Core\Exceptions\DomainException;

/**
 * Отказы раздела «Роли».
 */
final class RoleException extends DomainException
{
    public static function nameTaken(string $name): self
    {
        return new self(__('Роль «:name» уже есть.', ['name' => $name]));
    }

    public static function emptyName(): self
    {
        return new self(__('У роли должно быть название.'));
    }

    public static function codeRole(string $label): self
    {
        return new self(__('Роль «:role» задана в коде сайта: её нельзя переименовать или удалить, только поменять права.', ['role' => $label]));
    }

    public static function superAdminRole(): self
    {
        return new self(__('Права супер-администратора не настраиваются: у него всегда все права.'));
    }

    public static function hasUsers(string $label, int $count): self
    {
        return new self(__('Роль «:role» выдана пользователям (:count). Сначала смените им роль.', ['role' => $label, 'count' => $count]));
    }

    /**
     * @param array<int, string> $permissions
     */
    public static function unknownPermissions(array $permissions): self
    {
        return new self(__('Таких прав нет в коде сайта: :permissions.', ['permissions' => implode(', ', $permissions)]));
    }

    /**
     * @param array<int, string> $labels
     */
    public static function permissionsNotHeld(array $labels): self
    {
        return new self(__('Выдать или забрать можно только права, которые есть у вас. Нет у вас: :permissions.', ['permissions' => implode(', ', $labels)]));
    }
}
