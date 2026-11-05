<?php

namespace Zuber\Kit\Modules\Pages\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Zuber\Kit\Modules\Pages\Data\RemoteImageData;
use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;

/**
 * Скачивание картинки по ссылке для блоков (MCP upload-image): только транспорт, что можно скачивать
 * и что принять — решает BlockImageService.
 */
final readonly class RemoteImageClient
{
    private const int TIMEOUT = 15;

    public function __construct(private Factory $http) {}

    /**
     * @throws PageBuilderException
     */
    public function download(string $url): RemoteImageData
    {
        try {
            $response = $this->http->timeout(self::TIMEOUT)->withoutRedirecting()->get($url)->throw();
        } catch (ConnectionException|RequestException) {
            throw PageBuilderException::imageRejected(__('сервер по ссылке не отдал файл.'));
        }

        return new RemoteImageData($response->body(), (string) $response->header('Content-Type'));
    }
}
