<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\Services\SharedBlockService;

#[IsReadOnly]
final class ListSharedBlocksTool extends PagesTool
{
    protected string $name = 'list-shared-blocks';

    protected string $description = 'Общие блоки: id, название, тип, позиция (header — шапка, footer — подвал, пусто — ставится на страницы), на скольких страницах стоит, данные.';

    public function __construct(private readonly SharedBlockService $sharedBlocks) {}

    protected function respond(Request $request): ResponseFactory
    {
        return Response::structured([
            'shared_blocks' => $this->sharedBlocks->list()->map(fn (SharedBlock $block): array => [
                'id' => $block->id,
                'name' => $block->name,
                'type' => $block->type,
                'position' => $block->position?->value,
                'pages' => $block->pages_count ?? 0,
                'data' => $block->data ?? [],
            ])->all(),
        ]);
    }
}
