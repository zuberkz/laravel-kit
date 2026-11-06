<?php

namespace Zuber\Kit\Modules\Seo\Support;

/**
 * Канонический адрес страницы — на основном домене (APP_URL), даже если сайт открыли по www или
 * техническому домену. Из параметров остаётся только номер страницы списка, начиная со второй.
 *
 * Глобальной функции у кита нет: она заняла бы имя, под которым у сайта может быть свой хелпер
 * (функции пакета загружаются раньше функций проекта). В Blade — директива use этого класса и
 * CanonicalUrlSupport::current().
 */
final class CanonicalUrlSupport
{
    /**
     * Канонический адрес текущей страницы или переданного адреса (/sitemap.xml, полный URL).
     */
    public static function current(?string $url = null): string
    {
        $base = config('app.url');

        return self::make(is_string($base) ? $base : '', $url ?? request()->fullUrl());
    }

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
