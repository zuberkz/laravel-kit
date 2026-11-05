<?php

namespace Zuber\Kit\Modules\Security\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Заголовки безопасности на каждый ответ (глобальный middleware кита, правило security.md).
 * Заголовок, который ответ уже задал сам, не перезаписывается. HSTS — только на проде по https:
 * иначе браузер запомнит https для локального или тестового домена.
 */
final readonly class SecurityHeadersMiddleware
{
    public function __construct(
        private Repository $config,
        private Application $app,
    ) {}

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->config->get('kit.security_headers.enabled') !== true) {
            return $response;
        }

        collect($this->headers($request))
            ->filter(fn (string $value, string $name): bool => $value !== '' && ! $response->headers->has($name))
            ->each(fn (string $value, string $name) => $response->headers->set($name, $value));

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function headers(Request $request): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => $this->setting('frame_options'),
            'Referrer-Policy' => $this->setting('referrer_policy'),
            'Permissions-Policy' => $this->setting('permissions_policy'),
        ];

        if ($request->isSecure() && $this->app->isProduction()) {
            $headers['Strict-Transport-Security'] = sprintf('max-age=%s; includeSubDomains', $this->setting('hsts_max_age'));
        }

        return $headers;
    }

    private function setting(string $key): string
    {
        $value = $this->config->get('kit.security_headers.' . $key);

        return is_string($value) || is_int($value) ? (string) $value : '';
    }
}
