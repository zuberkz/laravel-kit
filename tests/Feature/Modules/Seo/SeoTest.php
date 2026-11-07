<?php

use Illuminate\Support\Carbon;
use Zuber\Kit\Modules\Seo\Contracts\SitemapSourceInterface;
use Zuber\Kit\Modules\Seo\Data\SitemapUrlData;
use Zuber\Kit\Modules\Seo\Support\CanonicalUrlSupport;

beforeEach(function (): void {
    config(['app.url' => 'https://example.kz']);
});

function production(): void
{
    app()->detectEnvironment(fn (): string => 'production');
}

it('closes the whole site in robots.txt outside production', function (): void {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSeeText("User-agent: *\nDisallow: /", false);
});

it('opens the site in production and points to the sitemap, listing only the configured paths', function (): void {
    production();
    config(['kit.seo.robots_disallow' => ['/cart']]);

    expect($this->get('http://www.example.kz/robots.txt')->assertOk()->getContent())
        ->toBe("User-agent: *\nDisallow: /cart\n\nSitemap: https://example.kz/sitemap.xml\n");
});

it('lists every url of the registered sources once, on APP_URL', function (): void {
    app()->instance('test.sitemap', new class implements SitemapSourceInterface
    {
        public function urls(): iterable
        {
            yield new SitemapUrlData('/');
            yield new SitemapUrlData('http://tech.example.kz/services/landing?utm=1', Carbon::parse('2026-10-01'));
            yield new SitemapUrlData('/services/landing');
        }
    });
    app()->tag(['test.sitemap'], SitemapSourceInterface::TAG);

    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

    expect($xml)->toContain('<loc>https://example.kz</loc>')
        ->toContain('<loc>https://example.kz/services/landing</loc><lastmod>2026-10-01</lastmod>')
        ->and(substr_count((string) $xml, '<url>'))->toBe(2);
});

it('describes the site in llms.txt from the sitemap sources, on APP_URL', function (): void {
    config(['app.name' => 'Студия']);
    app()->instance('test.sitemap', new class implements SitemapSourceInterface
    {
        public function urls(): iterable
        {
            yield new SitemapUrlData('/', null, 'Главная', 'Сайты под ключ в Алматы');
            yield new SitemapUrlData('http://tech.example.kz/services/landing', null, 'Лендинг', 'Продающая страница за неделю');
            yield new SitemapUrlData('/contacts');
        }
    });
    app()->tag(['test.sitemap'], SitemapSourceInterface::TAG);

    expect($this->get('http://www.example.kz/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent())
        ->toBe(implode("\n", [
            '# Студия',
            '',
            '> Сайты под ключ в Алматы',
            '',
            '## Страницы',
            '',
            '- [Главная](https://example.kz): Сайты под ключ в Алматы',
            '- [Лендинг](https://example.kz/services/landing): Продающая страница за неделю',
            '- [https://example.kz/contacts](https://example.kz/contacts)',
            '',
            '## Карта сайта',
            '',
            '- [sitemap.xml](https://example.kz/sitemap.xml)',
        ]) . "\n");
});

it('builds the canonical url on APP_URL and keeps only the page number', function (string $url, string $canonical): void {
    expect(CanonicalUrlSupport::current($url))->toBe($canonical);
})->with([
    'other host and query' => ['http://www.example.kz/blog?utm_source=x', 'https://example.kz/blog'],
    'second page' => ['https://example.kz/blog?page=2&sort=new', 'https://example.kz/blog?page=2'],
    'first page' => ['https://example.kz/blog?page=1', 'https://example.kz/blog'],
    'home' => ['https://example.kz/', 'https://example.kz'],
]);

it('leaves the name canonical_url to the site: the kit defines no global functions', function (): void {
    expect(function_exists('canonical_url'))->toBeFalse();
});

it('asks search engines not to index anything outside production', function (): void {
    $this->get('/robots.txt')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    production();

    $this->get('/robots.txt')->assertHeaderMissing('X-Robots-Tag');
});
