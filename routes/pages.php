<?php

use Illuminate\Support\Facades\Route;
use Zuber\Kit\Modules\Pages\Http\Controllers\BlockImageUploadController;
use Zuber\Kit\Modules\Pages\Http\Controllers\PagePreviewController;
use Zuber\Kit\Modules\Pages\Http\Middleware\AcceptJsonMiddleware;

Route::middleware(['web', 'signed'])
    ->get('_kit/pages/{page}/preview', PagePreviewController::class)
    ->name('kit.pages.preview');

// Картинка блока файлом по одноразовой ссылке из MCP image-upload-url: без сессии и CSRF — шлют curl
Route::middleware([AcceptJsonMiddleware::class, 'signed:relative', 'throttle:20,1'])
    ->post('_kit/pages/images/{token}', BlockImageUploadController::class)
    ->name('kit.pages.images.upload');
