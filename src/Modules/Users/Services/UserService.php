<?php

namespace Zuber\Kit\Modules\Users\Services;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Core\Support\PasswordSupport;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Data\MadeUserData;
use Zuber\Kit\Modules\Users\Data\UserData;
use Zuber\Kit\Modules\Users\Exceptions\SuperAdminRoleException;
use Zuber\Kit\Modules\Users\Exceptions\UnknownRoleException;
use Zuber\Kit\Modules\Users\Repositories\RoleRepository;
use Zuber\Kit\Modules\Users\Repositories\UserRepository;

/**
 * Создание и правка пользователя администратором: роли и пароль.
 */
final readonly class UserService
{
    public function __construct(
        private UserRepository $users,
        private UserAccessService $access,
        private Hasher $hasher,
        private RoleRepository $roles,
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

    /**
     * Пользователь из консоли (kit:make-user) — первый вход на сервере, где сидер не запускают. Без
     * проверки «кто назначает роль»: консоль — у того, кто владеет сервером. Пароль генерируется и
     * возвращается один раз; существующий пользователь не меняется, пока не попросили новый пароль.
     *
     * @throws UnknownRoleException
     */
    public function make(UserData $data, bool $resetPassword): MadeUserData
    {
        $user = $this->users->findByEmail($data->email);

        if ($user !== null) {
            return new MadeUserData($data->email, created: false, password: $resetPassword ? $this->resetPassword($user) : null);
        }

        if ($data->roles === []) {
            throw UnknownRoleException::none();
        }

        $missing = $this->roles->missing($data->roles);

        if ($missing !== []) {
            throw UnknownRoleException::roles($missing);
        }

        $password = PasswordSupport::generate();
        $this->users->create(['name' => $data->name, 'email' => $data->email, 'password' => $this->hasher->make($password)], $data->roles);

        return new MadeUserData($data->email, created: true, password: $password);
    }

    private function resetPassword(Model&HasRolesInterface $user): string
    {
        $password = PasswordSupport::generate();
        $this->users->updatePassword($user, $this->hasher->make($password));

        return $password;
    }
}
