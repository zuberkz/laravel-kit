<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Zuber\Kit\Modules\Pages\Actions\SaveSharedBlockAction;
use Zuber\Kit\Modules\Pages\Data\SharedBlockData;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\Services\PageBlocksService;
use Zuber\Kit\Modules\Pages\Services\SharedBlockService;

final class UpdateSharedBlockTool extends PagesTool
{
    protected string $name = 'update-shared-block';

    protected string $description = 'Изменить общий блок (шапку, подвал, повторяющийся раздел) — сразу на всех страницах, где он стоит. Переданные поля data заменяются, остальные остаются, null убирает поле; поля — по типу блока из list-block-types.';

    public function __construct(
        private readonly SharedBlockService $sharedBlocks,
        private readonly PageBlocksService $blocks,
        private readonly SaveSharedBlockAction $saveSharedBlock,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'shared_block_id' => $schema->integer()->description('id из list-shared-blocks')->required(),
            'name' => $schema->string()->description('Название в админке'),
            'data' => $schema->object()->description('Поля блока, которые меняются'),
        ];
    }

    protected function respond(Request $request): ResponseFactory
    {
        $input = $request->validate([
            'shared_block_id' => ['required', 'integer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'data' => ['sometimes', 'array'],
        ]);

        $block = $this->sharedBlocks->findOrFail($request->integer('shared_block_id'));
        /** @var array<string, mixed> $changes */
        $changes = is_array($input['data'] ?? null) ? $input['data'] : [];
        $data = array_filter([...$block->data ?? [], ...$changes], fn (mixed $value): bool => $value !== null);

        $saved = $this->saveSharedBlock->handle(new SharedBlockData(
            name: is_string($input['name'] ?? null) ? $input['name'] : $block->name,
            type: $block->type,
            data: $this->blocks->blockData($block->type, $data),
            position: $block->position,
        ), $block);

        return Response::structured($this->sharedBlock($saved));
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedBlock(SharedBlock $block): array
    {
        return [
            'id' => $block->id,
            'name' => $block->name,
            'type' => $block->type,
            'position' => $block->position?->value,
            'data' => $block->data ?? [],
        ];
    }
}
