<?php

namespace Zuber\Kit\Users\Data;

use Illuminate\Support\Arr;

/**
 * Пользователь из формы админки. Пароль — открытым текстом, хэширует сервис; null — не менять.
 */
final readonly class UserData
{
    /**
     * @param array<int, string> $roles имена ролей
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public array $roles,
    ) {}

    /**
     * @param array<string, mixed> $state данные формы после валидации Filament
     */
    public static function fromForm(array $state): self
    {
        $password = $state['password'] ?? null;

        return new self(
            name: Arr::string($state, 'name'),
            email: Arr::string($state, 'email'),
            password: is_string($password) && $password !== '' ? $password : null,
            roles: collect(Arr::array($state, 'roles'))->filter(fn (mixed $role): bool => is_string($role))->values()->all(),
        );
    }
}
