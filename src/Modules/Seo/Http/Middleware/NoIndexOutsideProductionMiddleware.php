<?php

namespace Zuber\Kit\Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тестовые стенды и локальные сайты не попадают в поиск, даже если robots.txt проигнорировали.
 */
final readonly class NoIndexOutsideProductionMiddleware
{
    public function __construct(private Application $app) {}

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->app->isProduction()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
