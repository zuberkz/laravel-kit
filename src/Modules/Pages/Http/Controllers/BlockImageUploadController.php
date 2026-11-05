<?php

namespace Zuber\Kit\Modules\Pages\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Zuber\Kit\Modules\Pages\Actions\ReceiveBlockImageAction;
use Zuber\Kit\Modules\Pages\Services\BlockImageService;
use Zuber\Kit\Modules\Pages\Services\BlockImageUploadService;

/**
 * Приём картинки блока по одноразовой ссылке (MCP image-upload-url): файл в поле file, ответ — путь
 * для поля картинки блока. Отказ (ссылка использована, не картинка) — 422 с сообщением.
 */
final class BlockImageUploadController
{
    public function __invoke(Request $request, string $token, ReceiveBlockImageAction $receiveImage, BlockImageService $images): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:' . BlockImageUploadService::MAX_KILOBYTES]]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $path = $receiveImage->handle($token, $file->getContent());

        return response()->json(['path' => $path, 'url' => $images->url($path)], 201);
    }
}
