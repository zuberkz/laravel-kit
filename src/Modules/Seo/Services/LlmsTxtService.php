<?php

namespace Zuber\Kit\Modules\Seo\Services;

use Illuminate\Contracts\Config\Repository;
use Zuber\Kit\Modules\Seo\Data\SitemapUrlData;
use Zuber\Kit\Modules\Seo\Support\CanonicalUrlSupport;

/**
 * llms.txt (llmstxt.org) для ИИ-ассистентов: название сайта, описание главной и страницы из
 * тех же разделов, что и sitemap.xml, с заголовками и описаниями.
 */
final readonly class LlmsTxtService
{
    public function __construct(
        private SitemapService $sitemap,
        private Repository $config,
    ) {}

    public function text(): string
    {
        $urls = collect($this->sitemap->urls());
        $summary = $urls->first(fn (SitemapUrlData $url): bool => $url->url === $this->url('/'))?->description;

        return collect(['# ' . $this->siteName(), ''])
            ->merge($this->filled($summary) ? ['> ' . $summary, ''] : [])
            ->push('## Страницы', '')
            ->merge($urls->map(fn (SitemapUrlData $url): string => $this->link($url)))
            ->push('', '## Карта сайта', '', '- [sitemap.xml](' . $this->url('/sitemap.xml') . ')')
            ->implode("\n") . "\n";
    }

    private function link(SitemapUrlData $url): string
    {
        $title = $this->filled($url->title) ? $url->title : $url->url;
        $line = "- [{$title}]({$url->url})";

        return $this->filled($url->description) ? "{$line}: {$url->description}" : $line;
    }

    private function siteName(): string
    {
        $name = $this->config->get('app.name');

        return is_string($name) ? $name : '';
    }

    private function url(string $path): string
    {
        $base = $this->config->get('app.url');

        return CanonicalUrlSupport::make(is_string($base) ? $base : '', $path);
    }

    /**
     * @phpstan-assert-if-true non-empty-string $value
     */
    private function filled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
