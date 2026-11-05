<?php

use Zuber\Kit\Seo\Support\CanonicalUrlSupport;

if (! function_exists('canonical_url')) {
    /**
     * Канонический адрес на APP_URL: текущей страницы или переданного адреса (правило seo.md).
     */
    function canonical_url(?string $url = null): string
    {
        $base = config('app.url');

        return CanonicalUrlSupport::make(is_string($base) ? $base : '', $url ?? request()->fullUrl());
    }
}
