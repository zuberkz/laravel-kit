<?php

namespace Zuber\Kit\Tests\Fixtures;

use Filament\Support\Contracts\HasLabel;

enum PermissionEnum: string implements HasLabel
{
    case ACCESS_ADMIN = 'admin.access';
    case MANAGE_USERS = 'users.manage';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACCESS_ADMIN => 'Вход в админку',
            self::MANAGE_USERS => 'Управление пользователями',
        };
    }
}
