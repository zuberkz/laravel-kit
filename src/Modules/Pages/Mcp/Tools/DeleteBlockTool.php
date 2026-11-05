<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Zuber\Kit\Modules\Pages\Actions\SavePageAction;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Services\PageBlocksService;
use Zuber\Kit\Modules\Pages\Services\PageService;

#[IsDestructive]
final class DeleteBlockTool extends PagesTool
{
    protected string $name = 'delete-block';

    protected string $description = 'Удалить блок страницы по номеру (index из get-page) — только по просьбе пользователя. Скрыть, не удаляя: update-block с data.hidden = true.';

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
        ];
    }

    protected function respond(Request $request): ResponseFactory
    {
        $request->validate(['page_id' => ['required', 'integer'], 'index' => ['required', 'integer']]);

        $page = $this->pages->findOrFail($request->integer('page_id'));
        $blocks = $this->blocks->remove($page->blocks ?? [], $request->integer('index'));

        return Response::structured($this->page($this->savePage->handle(PageData::fromPage($page, ['blocks' => $blocks]), $page)));
    }
}
