<?php

namespace Zuber\Kit\Modules\Pages\Data;

use Carbon\CarbonInterface;

/**
 * Одноразовая ссылка для отправки картинки блока файлом (MCP image-upload-url).
 */
final readonly class ImageUploadLinkData
{
    public function __construct(
        public string $url,
        public CarbonInterface $expiresAt,
    ) {}
}
