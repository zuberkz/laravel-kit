<?php

namespace Zuber\Kit\Modules\Users\Actions;

use Illuminate\Database\ConnectionInterface;
use Zuber\Kit\Modules\Users\Data\MadeUserData;
use Zuber\Kit\Modules\Users\Data\UserData;
use Zuber\Kit\Modules\Users\Exceptions\UnknownRoleException;
use Zuber\Kit\Modules\Users\Services\UserService;

/**
 * Владелец сервера заводит пользователя из консоли (kit:make-user): пользователь и роли — одной
 * транзакцией; повторный запуск ничего не дублирует.
 */
final readonly class MakeUserAction
{
    public function __construct(
        private ConnectionInterface $db,
        private UserService $users,
    ) {}

    /**
     * @throws UnknownRoleException
     */
    public function handle(UserData $data, bool $resetPassword = false): MadeUserData
    {
        return $this->db->transaction(fn (): MadeUserData => $this->users->make($data, $resetPassword));
    }
}
