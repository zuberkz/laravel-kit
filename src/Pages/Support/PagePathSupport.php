<?php

namespace Zuber\Kit\Pages\Support;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Адреса страниц конструктора: формат и занятость маршрутами сайта (админка, robots.txt, свои
 * разделы проекта). Страница с занятым адресом никогда не откроется — маршрут сайта раньше.
 */
final class PagePathSupport
{
    public const string PATTERN = '/^\/([a-z0-9]+(-[a-z0-9]+)*(\/[a-z0-9]+(-[a-z0-9]+)*)*)?$/';

    public static function isTakenByRoute(Router $router, string $path): bool
    {
        try {
            $route = $router->getRoutes()->match(Request::create($path));
        } catch (HttpException) {
            return false;
        }

        return ! $route->isFallback && $route->getName() !== 'home';
    }
}
