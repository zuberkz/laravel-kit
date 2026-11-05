<?php

namespace Zuber\Kit\Modules\Pages\Services;

use finfo;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;
use Zuber\Kit\Modules\Pages\Clients\RemoteImageClient;
use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;

/**
 * Картинка для блока по ссылке (MCP upload-image): только https и только публичный адрес — сервер
 * сайта не ходит во внутреннюю сеть; принимаются JPEG, PNG, WebP, GIF и AVIF до 10 МБ, тип — по
 * содержимому, а не по ответу сервера. Файл ложится туда же, куда загрузки из админки.
 */
final readonly class BlockImageUploadService
{
    private const int MAX_BYTES = 10 * 1024 * 1024;

    /**
     * @var array<string, string> тип содержимого => расширение файла
     */
    private const array EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
    ];

    public function __construct(
        private RemoteImageClient $client,
        private BlockImageService $images,
        private Factory $filesystems,
    ) {}

    /**
     * @throws PageBuilderException
     *
     * @return string путь для поля картинки в данных блока
     */
    public function storeFromUrl(string $url): string
    {
        $this->assertPublicHttps($url);

        $image = $this->client->download($url);

        if (strlen($image->content) > self::MAX_BYTES) {
            throw PageBuilderException::imageRejected(__('файл больше 10 МБ.'));
        }

        $type = (new finfo(FILEINFO_MIME_TYPE))->buffer($image->content);
        $extension = is_string($type) ? (self::EXTENSIONS[$type] ?? null) : null;

        if ($extension === null) {
            throw PageBuilderException::imageRejected(__('это не картинка JPEG, PNG, WebP, GIF или AVIF.'));
        }

        $path = $this->images->directory() . '/' . Str::uuid()->toString() . '.' . $extension;
        $this->filesystems->disk($this->images->diskName())->put($path, $image->content);

        return $path;
    }

    /**
     * @throws PageBuilderException
     */
    private function assertPublicHttps(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! is_string($host) || $host === '') {
            throw PageBuilderException::imageRejected(__('нужна ссылка https://.'));
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : (gethostbynamel($host) ?: []);
        $public = $addresses !== [] && collect($addresses)->every(
            fn (string $address): bool => filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false,
        );

        if (! $public) {
            throw PageBuilderException::imageRejected(__('адрес не найден или ведёт во внутреннюю сеть.'));
        }
    }
}
