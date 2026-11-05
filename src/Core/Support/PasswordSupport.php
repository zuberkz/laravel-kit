<?php

namespace Zuber\Kit\Core\Support;

/**
 * Пароли, которые администратор выдаёт пользователю: их читают с экрана и диктуют, поэтому без
 * похожих символов (0/O, 1/l/I) и группами — Kp7m-Xw3q-Rt9z.
 */
final class PasswordSupport
{
    private const string ALPHABET = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const int GROUP_LENGTH = 4;

    /**
     * Три группы — 12 символов из 56, около 70 бит: строчная, заглавная и цифра есть всегда.
     */
    public static function generate(int $groups = 3): string
    {
        do {
            $password = collect(range(1, $groups))
                ->map(fn (): string => self::group())
                ->implode('-');
        } while (! self::hasEveryKind($password));

        return $password;
    }

    private static function group(): string
    {
        return collect(range(1, self::GROUP_LENGTH))
            ->map(fn (): string => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)])
            ->implode('');
    }

    private static function hasEveryKind(string $password): bool
    {
        return preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/\d/', $password) === 1;
    }
}
