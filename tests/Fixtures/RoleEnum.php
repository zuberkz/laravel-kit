<?php

namespace Zuber\Kit\Tests\Fixtures;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Zuber\Kit\Access\Contracts\RoleEnumInterface;

enum RoleEnum: string implements HasDescription, HasLabel, RoleEnumInterface
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Супер-администратор',
            self::ADMIN => 'Администратор',
            self::MANAGER => 'Менеджер',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Разработчик',
            self::ADMIN => 'Управляет пользователями',
            self::MANAGER => 'Без доступа к пользователям',
        };
    }

    /**
     * @return array<int, PermissionEnum>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN, self::ADMIN => PermissionEnum::cases(),
            self::MANAGER => [PermissionEnum::ACCESS_ADMIN],
        };
    }
}
