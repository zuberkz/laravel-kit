<?php

namespace Zuber\Kit\Modules\Seo\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use XMLWriter;
use Zuber\Kit\Modules\Seo\Contracts\SitemapSourceInterface;
use Zuber\Kit\Modules\Seo\Data\SitemapUrlData;
use Zuber\Kit\Modules\Seo\Support\CanonicalUrlSupport;

/**
 * sitemap.xml из разделов, которые зарегистрировал проект; адреса — на APP_URL.
 */
final readonly class SitemapService
{
    /**
     * @param iterable<int, SitemapSourceInterface> $sources
     */
    public function __construct(
        private iterable $sources,
        private Repository $config,
    ) {}

    public function xml(): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        collect($this->urls())->each(fn (SitemapUrlData $url) => $this->writeUrl($xml, $url));

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Адреса всех разделов по одному разу, уже на APP_URL; их же перечисляет llms.txt.
     *
     * @return array<int, SitemapUrlData>
     */
    public function urls(): array
    {
        return collect($this->sources)
            ->flatMap(fn (SitemapSourceInterface $source): Collection => collect($source->urls()))
            ->map(fn (SitemapUrlData $url): SitemapUrlData => new SitemapUrlData(
                $this->canonical($url->url),
                $url->lastModified,
                $url->title,
                $url->description,
            ))
            ->unique(fn (SitemapUrlData $url): string => $url->url)
            ->values()
            ->all();
    }

    private function writeUrl(XMLWriter $xml, SitemapUrlData $url): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $url->url);

        if ($url->lastModified !== null) {
            $xml->writeElement('lastmod', $url->lastModified->format('Y-m-d'));
        }

        $xml->endElement();
    }

    private function canonical(string $url): string
    {
        $base = $this->config->get('app.url');

        return CanonicalUrlSupport::make(is_string($base) ? $base : '', $url);
    }
}
