<?php

namespace Zuber\Kit\Modules\Seo\Contracts;

use Zuber\Kit\Modules\Seo\Data\SitemapUrlData;

/**
 * Раздел сайта для sitemap.xml. Проект регистрирует свои разделы тегом в сервис-провайдере:
 *
 *     $this->app->tag([PageSitemapSource::class], SitemapSourceInterface::TAG);
 */
interface SitemapSourceInterface
{
    public const string TAG = 'kit.sitemap.sources';

    /**
     * Только опубликованные записи.
     *
     * @return iterable<int, SitemapUrlData>
     */
    public function urls(): iterable;
}
