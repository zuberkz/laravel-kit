<?php

namespace Zuber\Kit\Modules\Seo\Data;

use DateTimeInterface;

/**
 * Адрес в sitemap.xml: путь или полный адрес (приводится к APP_URL) и дата последнего изменения.
 * Заголовок и описание попадают в llms.txt; без заголовка ссылка в llms.txt подписана адресом.
 */
final readonly class SitemapUrlData
{
    public function __construct(
        public string $url,
        public ?DateTimeInterface $lastModified = null,
        public ?string $title = null,
        public ?string $description = null,
    ) {}
}
