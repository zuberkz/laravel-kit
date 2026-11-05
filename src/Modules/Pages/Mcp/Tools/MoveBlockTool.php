<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Zuber\Kit\Modules\Pages\Actions\SavePageAction;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Services\PageBlocksService;
use Zuber\Kit\Modules\Pages\Services\PageService;

final class MoveBlockTool extends PagesTool
{
    protected string $name = 'move-block';

    protected string $description = 'Переставить блок страницы: с номера from на номер to (номера из get-page, с 0).';

    public function __construct(
        private readonly PageService $pages,
        private readonly PageBlocksService $blocks,
        private readonly SavePageAction $savePage,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->description('id страницы из list-pages')->required(),
            'from' => $schema->integer()->description('Текущий номер блока')->required(),
            'to' => $schema->integer()->description('Новый номер блока')->required(),
        ];
    }

    protected function respond(Request $request): ResponseFactory
    {
        $request->validate(['page_id' => ['required', 'integer'], 'from' => ['required', 'integer'], 'to' => ['required', 'integer']]);

        $page = $this->pages->findOrFail($request->integer('page_id'));
        $blocks = $this->blocks->move($page->blocks ?? [], $request->integer('from'), $request->integer('to'));

        return Response::structured($this->page($this->savePage->handle(PageData::fromPage($page, ['blocks' => $blocks]), $page)));
    }
}
