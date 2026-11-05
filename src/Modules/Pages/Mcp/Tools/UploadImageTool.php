<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Zuber\Kit\Modules\Pages\Actions\UploadBlockImageAction;
use Zuber\Kit\Modules\Pages\Services\BlockImageService;

#[IsOpenWorld]
final class UploadImageTool extends PagesTool
{
    protected string $name = 'upload-image';

    protected string $description = 'Загрузить картинку для блока по https-ссылке (JPEG, PNG, WebP, GIF, AVIF до 10 МБ). Ответ — path: его ставят в поле картинки блока (image, logo, photo…) через add-block или update-block.';

    public function __construct(
        private readonly UploadBlockImageAction $uploadImage,
        private readonly BlockImageService $images,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->description('Ссылка https:// на картинку')->required(),
        ];
    }

    protected function respond(Request $request): ResponseFactory
    {
        $request->validate(['url' => ['required', 'string', 'url:https', 'max:2048']]);

        $path = $this->uploadImage->handle($request->string('url')->toString());

        return Response::structured(['path' => $path, 'url' => $this->images->url($path)]);
    }
}
