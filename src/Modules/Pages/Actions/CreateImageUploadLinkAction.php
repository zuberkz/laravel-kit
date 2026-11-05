<?php

namespace Zuber\Kit\Modules\Pages\Actions;

use Zuber\Kit\Modules\Pages\Data\ImageUploadLinkData;
use Zuber\Kit\Modules\Pages\Services\BlockImageUploadService;

/**
 * Ассистент просит ссылку, чтобы отправить картинку блока файлом (MCP image-upload-url). Транзакции
 * нет — запись только токена в кеш.
 */
final readonly class CreateImageUploadLinkAction
{
    public function __construct(private BlockImageUploadService $uploads) {}

    public function handle(): ImageUploadLinkData
    {
        return $this->uploads->issueLink();
    }
}
