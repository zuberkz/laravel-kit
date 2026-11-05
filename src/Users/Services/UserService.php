<?php

namespace Zuber\Kit\Users\Services;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Support\PasswordSupport;
use Zuber\Kit\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Users\Data\UserData;
use Zuber\Kit\Users\Exceptions\SuperAdminRoleException;
use Zuber\Kit\Users\Repositories\UserRepository;

/**
 * Создание и правка пользователя администратором: роли и пароль.
 */
final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
        private UserAccessService $access,
        private Hasher $hasher,
    ) {}

    /**
     * Без пароля в форме пользователь получает случайный: войти он сможет, когда пароль зададут.
     *
     * @throws SuperAdminRoleException
     */
    public function create(UserData $data, HasRolesInterface $actor): Model&HasRolesInterface
    {
        $this->access->ensureCanAssign($actor, $data->roles, []);

        return $this->users->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $this->hasher->make($data->password ?? PasswordSupport::generate()),
        ], $data->roles);
    }

    /**
     * @throws SuperAdminRoleException
     */
    public function update(Model&HasRolesInterface $user, UserData $data, HasRolesInterface $actor): Model&HasRolesInterface
    {
        $this->access->ensureCanAssign($actor, $data->roles, $user->getRoleNames()->all());

        $attributes = ['name' => $data->name, 'email' => $data->email];

        if ($data->password !== null) {
            $attributes['password'] = $this->hasher->make($data->password);
        }

        return $this->users->update($user, $attributes, $data->roles);
    }
}
