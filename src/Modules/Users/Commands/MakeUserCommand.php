<?php

namespace Zuber\Kit\Modules\Users\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Zuber\Kit\Modules\Users\Actions\MakeUserAction;
use Zuber\Kit\Modules\Users\Data\UserData;
use Zuber\Kit\Modules\Users\Exceptions\UnknownRoleException;

/**
 * Первый вход в админку на сервере: сидер там не запускают, пользователей нет. Пароль генерируется и
 * печатается один раз; повторный запуск для той же почты ничего не меняет, --reset-password — новый пароль.
 */
final class MakeUserCommand extends Command
{
    protected $signature = 'kit:make-user
        {email : Почта для входа в админку}
        {--name= : Имя; по умолчанию — часть почты до @}
        {--role=* : Имя роли из раздела «Роли» (super_admin, admin, manager…), можно несколько}
        {--reset-password : Выдать новый пароль существующему пользователю}';

    protected $description = 'Завести пользователя админки с ролью и сгенерированным паролем';

    public function handle(MakeUserAction $makeUser): int
    {
        $argument = $this->argument('email');
        $email = Str::lower(trim(is_string($argument) ? $argument : ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error(__('Почта «:email» не похожа на адрес.', ['email' => $email]));

            return self::INVALID;
        }

        $name = $this->option('name');
        $roles = collect((array) $this->option('role'))->filter(fn (mixed $role): bool => is_string($role) && $role !== '')->values()->all();

        try {
            $result = $makeUser->handle(
                new UserData(is_string($name) && $name !== '' ? $name : Str::before($email, '@'), $email, null, $roles),
                (bool) $this->option('reset-password'),
            );
        } catch (UnknownRoleException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(match (true) {
            $result->created => __('Пользователь :email создан.', ['email' => $result->email]),
            $result->password !== null => __('Пользователю :email выдан новый пароль.', ['email' => $result->email]),
            default => __('Пользователь :email уже есть — ничего не меняю. Новый пароль: --reset-password.', ['email' => $result->email]),
        });

        if ($result->password !== null) {
            $this->components->twoColumnDetail(__('Пароль (показывается один раз)'), $result->password);
        }

        return self::SUCCESS;
    }
}
