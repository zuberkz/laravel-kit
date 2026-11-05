<?php

namespace Zuber\Kit\Users\Actions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Users\Data\UserData;
use Zuber\Kit\Users\Exceptions\SuperAdminRoleException;
use Zuber\Kit\Users\Services\UserService;

/**
 * Администратор меняет пользователя: имя, email, пароль (если задан) и роли — одной транзакцией.
 */
final readonly class UpdateUserAction
{
    public function __construct(
        private ConnectionInterface $db,
        private UserService $users,
    ) {}

    /**
     * @throws SuperAdminRoleException
     */
    public function handle(Model&HasRolesInterface $user, UserData $data, HasRolesInterface $actor): Model&HasRolesInterface
    {
        return $this->db->transaction(fn (): Model&HasRolesInterface => $this->users->update($user, $data, $actor));
    }
}
