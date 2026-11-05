<?php

namespace Zuber\Kit\Modules\Seo\Support;

/**
 * Канонический адрес страницы — на основном домене (APP_URL), даже если сайт открыли по www или
 * техническому домену. Из параметров остаётся только номер страницы списка, начиная со второй.
 */
final class CanonicalUrlSupport
{
    public static function make(string $baseUrl, string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $page = filter_var($query['page'] ?? null, FILTER_VALIDATE_INT);
        $canonical = rtrim($baseUrl, '/') . '/' . ltrim(is_string($path) ? $path : '', '/');

        return is_int($page) && $page > 1 ? $canonical . '?page=' . $page : rtrim($canonical, '/');
    }
}
