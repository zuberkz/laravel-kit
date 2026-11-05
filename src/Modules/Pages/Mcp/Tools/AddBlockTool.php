<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Zuber\Kit\Modules\Pages\Actions\SavePageAction;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Services\PageBlocksService;
use Zuber\Kit\Modules\Pages\Services\PageService;

final class AddBlockTool extends PagesTool
{
    protected string $name = 'add-block';

    protected string $description = 'Добавить блок на страницу: type и data из list-block-types (общий блок — type "shared", data.shared_block_id). position — номер, куда вставить (с 0), без него — в конец. Номера следующих блоков сдвигаются.';

    public function __construct(
        private readonly PageService $pages,
        private readonly PageBlocksService $blocks,
        private readonly SavePageAction $savePage,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->description('id страницы из list-pages')->required(),
            'type' => $schema->string()->description('Тип блока из list-block-types или shared')->required(),
            'data' => $schema->object()->description('Поля блока и настройки оформления'),
            'position' => $schema->integer()->description('Куда вставить, номер с 0; без него — в конец'),
        ];
    }

    protected function respond(Request $request): Response
    {
        $input = $request->validate([
            'page_id' => ['required', 'integer'],
            'type' => ['required', 'string'],
            'data' => ['nullable', 'array'],
            'position' => ['nullable', 'integer'],
        ]);

        $page = $this->pages->findOrFail($request->integer('page_id'));
        $blocks = $this->blocks->insert(
            $page->blocks ?? [],
            ['type' => $input['type'], 'data' => is_array($input['data'] ?? null) ? $input['data'] : []],
            isset($input['position']) ? $request->integer('position') : null,
        );

        return Response::json($this->page($this->savePage->handle(PageData::fromPage($page, ['blocks' => $blocks]), $page)));
    }
}
