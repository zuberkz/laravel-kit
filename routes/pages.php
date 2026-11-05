<?php

use Illuminate\Support\Facades\Route;
use Zuber\Kit\Pages\Http\Controllers\PagePreviewController;

Route::middleware(['web', 'signed'])
    ->get('_kit/pages/{page}/preview', PagePreviewController::class)
    ->name('kit.pages.preview');
