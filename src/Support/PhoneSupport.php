<?php

namespace Zuber\Kit\Support;

/**
 * Казахстанские номера (+7): в базе — +77071234567, на экране — +7 (707) 123-45-67.
 */
final class PhoneSupport
{
    /**
     * Любая запись номера — в +77071234567; не номер из 10 цифр после +7 / 8 — null.
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if (strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true)) {
            $digits = substr($digits, 1);
        }

        return strlen($digits) === 10 ? '+7' . $digits : null;
    }

    public static function format(?string $phone): ?string
    {
        $normalized = self::normalize($phone);

        if ($normalized === null) {
            return $phone;
        }

        return sprintf('+7 (%s) %s-%s-%s', substr($normalized, 2, 3), substr($normalized, 5, 3), substr($normalized, 8, 2), substr($normalized, 10, 2));
    }
}
