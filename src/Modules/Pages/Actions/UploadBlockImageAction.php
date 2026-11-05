<?php

namespace Zuber\Kit\Modules\Pages\Actions;

use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;
use Zuber\Kit\Modules\Pages\Services\BlockImageUploadService;

/**
 * Картинка по ссылке для блока страницы (MCP upload-image): файл сохраняется в каталог картинок
 * блоков, путь потом ставят в поле картинки блока. Транзакции нет — запись только в хранилище.
 */
final readonly class UploadBlockImageAction
{
    public function __construct(private BlockImageUploadService $uploads) {}

    /**
     * @throws PageBuilderException
     */
    public function handle(string $url): string
    {
        return $this->uploads->storeFromUrl($url);
    }
}
