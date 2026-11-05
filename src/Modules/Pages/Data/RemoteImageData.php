<?php

namespace Zuber\Kit\Modules\Pages\Data;

/**
 * Скачанный по ссылке файл: содержимое и тип из ответа сервера.
 */
final readonly class RemoteImageData
{
    public function __construct(
        public string $content,
        public string $contentType,
    ) {}
}
