<?php

namespace Zuber\Kit\Modules\Seo\Data;

use DateTimeInterface;

/**
 * Адрес в sitemap.xml: путь или полный адрес (приводится к APP_URL) и дата последнего изменения.
 */
final readonly class SitemapUrlData
{
    public function __construct(
        public string $url,
        public ?DateTimeInterface $lastModified = null,
    ) {}
}
