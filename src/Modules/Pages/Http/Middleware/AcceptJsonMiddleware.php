<?php

namespace Zuber\Kit\Modules\Pages\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Маршрут для программ (curl из песочницы ассистента), а не для браузера: ошибки проверки и отказы
 * всегда JSON, даже без заголовка Accept, — редиректу с сообщением в сессии здесь некуда вести.
 */
final class AcceptJsonMiddleware
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
