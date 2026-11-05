<?php

namespace Zuber\Kit\Modules\Pages\Filament\Forms\Fields;

use Filament\Forms\Components\FileUpload;
use Zuber\Kit\Modules\Pages\Services\BlockImageService;

/**
 * Картинка блока: диск и каталог — из kit.pages, до 4 МБ, WebP/JPEG/PNG/SVG, с обрезкой в редакторе.
 * Файлы, которые после сохранения нигде не упомянуты, кит удаляет сам.
 */
final class BlockImageUpload
{
    public static function make(string $name = 'image'): FileUpload
    {
        $images = app(BlockImageService::class);

        return FileUpload::make($name)
            ->label(__('Картинка'))
            ->image()
            ->imageEditor()
            ->disk($images->diskName())
            ->directory($images->directory())
            ->visibility('public')
            ->maxSize(4096)
            ->acceptedFileTypes(['image/webp', 'image/jpeg', 'image/png', 'image/svg+xml']);
    }
}
