<?php

namespace Zuber\Kit\Modules\Pages\Actions;

use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;
use Zuber\Kit\Modules\Pages\Services\BlockImageUploadService;

/**
 * Файл картинки пришёл по одноразовой ссылке: ссылка гасится, файл ложится в каталог картинок блоков,
 * путь потом ставят в поле картинки блока. Транзакции нет — запись только в кеш и хранилище.
 */
final readonly class ReceiveBlockImageAction
{
    public function __construct(private BlockImageUploadService $uploads) {}

    /**
     * @throws PageBuilderException
     */
    public function handle(string $token, string $content): string
    {
        return $this->uploads->storeUploaded($token, $content);
    }
}
