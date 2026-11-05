<?php

namespace Zuber\Kit\Modules\Pages\Services;

use finfo;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Str;
use Zuber\Kit\Modules\Pages\Clients\RemoteImageClient;
use Zuber\Kit\Modules\Pages\Data\ImageUploadLinkData;
use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;

/**
 * Картинки для блоков не из админки (MCP): по https-ссылке (upload-image) или файлом по одноразовой
 * ссылке (image-upload-url) — фото из чата или с диска, которое ассистент отправляет из своей
 * песочницы. Ссылка на скачивание — только https и только публичный адрес: сервер сайта не ходит во
 * внутреннюю сеть. Принимаются JPEG, PNG, WebP, GIF и AVIF до 10 МБ, тип — по содержимому, а не по
 * имени или ответу сервера. Файл ложится туда же, куда загрузки из админки.
 */
final readonly class BlockImageUploadService
{
    public const int MAX_KILOBYTES = 10 * 1024;
    private const int LINK_MINUTES = 10;

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
        private Cache $cache,
        private UrlGenerator $urls,
    ) {}

    /**
     * @throws PageBuilderException
     *
     * @return string путь для поля картинки в данных блока
     */
    public function storeFromUrl(string $url): string
    {
        $this->assertPublicHttps($url);

        $content = $this->client->download($url)->content;

        return $this->put($content, $this->extension($content));
    }

    /**
     * Одноразовая ссылка для отправки файла: подписана, живёт 10 минут, токен в ней срабатывает один раз.
     */
    public function issueLink(): ImageUploadLinkData
    {
        $token = Str::random(40);
        $expiresAt = now()->addMinutes(self::LINK_MINUTES);

        $this->cache->put($this->tokenKey($token), true, $expiresAt);

        return new ImageUploadLinkData(
            // Подпись по пути без схемы и домена: за прокси (https снаружи, http внутри) она та же
            url: $this->urls->to($this->urls->temporarySignedRoute('kit.pages.images.upload', $expiresAt, ['token' => $token], absolute: false)),
            expiresAt: $expiresAt,
        );
    }

    /**
     * Файл по одноразовой ссылке: сначала проверка картинки — ошибка в файле не тратит ссылку, — потом
     * ссылка гасится и файл сохраняется.
     *
     *
     * @throws PageBuilderException
     *
     * @return string путь для поля картинки в данных блока
     */
    public function storeUploaded(string $token, string $content): string
    {
        $extension = $this->extension($content);

        if ($this->cache->pull($this->tokenKey($token)) !== true) {
            throw PageBuilderException::uploadLinkUsed();
        }

        return $this->put($content, $extension);
    }

    /**
     * @throws PageBuilderException
     */
    private function extension(string $content): string
    {
        if (strlen($content) > self::MAX_KILOBYTES * 1024) {
            throw PageBuilderException::imageRejected(__('файл больше 10 МБ.'));
        }

        $type = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);

        return (is_string($type) ? (self::EXTENSIONS[$type] ?? null) : null)
            ?? throw PageBuilderException::imageRejected(__('это не картинка JPEG, PNG, WebP, GIF или AVIF.'));
    }

    private function put(string $content, string $extension): string
    {
        $path = $this->images->directory() . '/' . Str::uuid()->toString() . '.' . $extension;
        $this->filesystems->disk($this->images->diskName())->put($path, $content);

        return $path;
    }

    private function tokenKey(string $token): string
    {
        return 'kit-pages-image-upload:' . $token;
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
