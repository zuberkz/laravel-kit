<?php

namespace Zuber\Kit\Modules\Seo\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Zuber\Kit\Modules\Seo\Support\CanonicalUrlSupport;

/**
 * robots.txt: вне прода индексация закрыта целиком. Путь админки сюда не пишется — по robots.txt
 * его нашли бы боты; админку закрывает вход.
 */
final readonly class RobotsService
{
    public function __construct(
        private Repository $config,
        private Application $app,
    ) {}

    public function text(): string
    {
        if (! $this->app->isProduction()) {
            return "User-agent: *\nDisallow: /\n";
        }

        $disallow = collect((array) $this->config->get('kit.seo.robots_disallow'))
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->map(fn (string $path): string => 'Disallow: ' . $path);

        return collect(['User-agent: *'])
            ->merge($disallow->isEmpty() ? ['Disallow:'] : $disallow)
            ->push('', 'Sitemap: ' . CanonicalUrlSupport::make($this->appUrl(), '/sitemap.xml'))
            ->implode("\n") . "\n";
    }

    private function appUrl(): string
    {
        $url = $this->config->get('app.url');

        return is_string($url) ? $url : '';
    }
}
