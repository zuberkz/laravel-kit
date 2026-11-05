<?php

namespace Zuber\Kit\Modules\Users\Actions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Data\UserData;
use Zuber\Kit\Modules\Users\Exceptions\SuperAdminRoleException;
use Zuber\Kit\Modules\Users\Services\UserService;

/**
 * Администратор создаёт пользователя с ролями: пользователь и роли — одной транзакцией.
 */
final readonly class CreateUserAction
{
    public function __construct(
        private ConnectionInterface $db,
        private UserService $users,
    ) {}

    /**
     * @throws SuperAdminRoleException
     */
    public function handle(UserData $data, HasRolesInterface $actor): Model&HasRolesInterface
    {
        return $this->db->transaction(fn (): Model&HasRolesInterface => $this->users->create($data, $actor));
    }
}
