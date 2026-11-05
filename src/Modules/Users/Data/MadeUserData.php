<?php

namespace Zuber\Kit\Modules\Users\Data;

/**
 * Итог kit:make-user: создан ли пользователь и новый пароль (null — пароль не менялся).
 */
final readonly class MadeUserData
{
    public function __construct(
        public string $email,
        public bool $created,
        public ?string $password,
    ) {}
}
