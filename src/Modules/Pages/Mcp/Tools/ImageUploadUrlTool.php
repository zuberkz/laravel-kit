<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Zuber\Kit\Modules\Pages\Actions\CreateImageUploadLinkAction;

final class ImageUploadUrlTool extends PagesTool
{
    protected string $name = 'image-upload-url';

    protected string $description = 'Одноразовая ссылка, чтобы отправить картинку блока файлом — фото, приложенное к чату, или файл с диска. Отправь его командой curl из ответа (в песочнице выполнения кода; файлы чата — в /mnt/user-data/uploads), в ответе curl — path для поля картинки блока. Ссылка живёт 10 минут и срабатывает один раз. Картинка по https-ссылке — upload-image.';

    public function __construct(private readonly CreateImageUploadLinkAction $createLink) {}

    protected function respond(Request $request): ResponseFactory
    {
        $link = $this->createLink->handle();

        return Response::structured([
            'upload_url' => $link->url,
            'expires_at' => $link->expiresAt->toIso8601String(),
            'curl' => "curl -sS -F 'file=@/путь/к/картинке.jpg' '{$link->url}'",
            'response' => '{"path": "blocks/….jpg", "url": "…"} — path поставь в поле картинки блока (add-block, update-block)',
        ]);
    }
}
