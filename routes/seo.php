<?php

use Illuminate\Support\Facades\Route;
use Zuber\Kit\Modules\Seo\Http\Controllers\LlmsTxtController;
use Zuber\Kit\Modules\Seo\Http\Controllers\RobotsController;
use Zuber\Kit\Modules\Seo\Http\Controllers\SitemapController;

// Статичные public/robots.txt и public/llms.txt nginx отдаёт раньше Laravel — в проекте их быть не должно
Route::get('robots.txt', RobotsController::class)->name('kit.robots');
Route::get('sitemap.xml', SitemapController::class)->name('kit.sitemap');
Route::get('llms.txt', LlmsTxtController::class)->name('kit.llms');
