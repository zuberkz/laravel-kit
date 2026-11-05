<?php

use Illuminate\Support\Facades\Route;
use Zuber\Kit\Seo\Http\Controllers\RobotsController;
use Zuber\Kit\Seo\Http\Controllers\SitemapController;

// Статичный public/robots.txt nginx отдаёт раньше Laravel — в проекте его быть не должно
Route::get('robots.txt', RobotsController::class)->name('kit.robots');
Route::get('sitemap.xml', SitemapController::class)->name('kit.sitemap');
