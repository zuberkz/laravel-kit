<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Zuber\Kit\Modules\Pages\Actions\SavePageAction;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Services\PageBlocksService;
use Zuber\Kit\Modules\Pages\Services\PageService;

final class UpdateBlockTool extends PagesTool
{
    protected string $name = 'update-block';

    protected string $description = 'Изменить данные блока страницы по номеру (index из get-page): переданные поля заменяются, остальные остаются, null убирает поле. Тип блока не меняется — для этого delete-block и add-block.';

    public function __construct(
        private readonly PageService $pages,
        private readonly PageBlocksService $blocks,
        private readonly SavePageAction $savePage,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->description('id страницы из list-pages')->required(),
            'index' => $schema->integer()->description('Номер блока из get-page, с 0')->required(),
            'data' => $schema->object()->description('Поля блока и настройки оформления, которые меняются')->required(),
        ];
    }

    protected function respond(Request $request): Response
    {
        $input = $request->validate(['page_id' => ['required', 'integer'], 'index' => ['required', 'integer'], 'data' => ['required', 'array']]);

        $page = $this->pages->findOrFail($request->integer('page_id'));
        /** @var array<string, mixed> $changes */
        $changes = is_array($input['data']) ? $input['data'] : [];
        $blocks = $this->blocks->change($page->blocks ?? [], $request->integer('index'), $changes);

        return Response::json($this->page($this->savePage->handle(PageData::fromPage($page, ['blocks' => $blocks]), $page)));
    }
}
