<?php

namespace Zuber\Kit\Modules\Users\Data;

/**
 * Итог kit:make-user: создан ли пользователь, сгенерированный пароль для показа (null — пароль не
 * генерировался) и сменился ли пароль: введённый вручную пароль не возвращается.
 */
final readonly class MadeUserData
{
    public function __construct(
        public string $email,
        public bool $created,
        public ?string $password,
        public bool $passwordChanged = false,
    ) {}
}
