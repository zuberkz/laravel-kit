<?php

namespace Zuber\Kit\Modules\Security;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;
use Zuber\Kit\Modules\Security\Http\Middleware\SecurityHeadersMiddleware;

/**
 * Заголовки безопасности на каждый ответ (kit.security_headers).
 */
final class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Ядро — через контракт: по классу Kernel контейнер собрал бы новое, мимо ядра, которое обслуживает запросы
        $this->app->make(HttpKernel::class)->pushMiddleware(SecurityHeadersMiddleware::class);
    }
}
