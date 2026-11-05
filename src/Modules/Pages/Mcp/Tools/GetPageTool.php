<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Zuber\Kit\Modules\Pages\Services\PageService;

#[IsReadOnly]
final class GetPageTool extends PagesTool
{
    protected string $name = 'get-page';

    protected string $description = 'Страница целиком: настройки, SEO и блоки по номерам (index с 0) с данными. preview_url — предпросмотр черновика на час.';

    public function __construct(private readonly PageService $pages) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->description('id страницы из list-pages')->required(),
        ];
    }

    protected function respond(Request $request): ResponseFactory
    {
        $request->validate(['page_id' => ['required', 'integer']]);

        return Response::structured($this->page($this->pages->findOrFail($request->integer('page_id'))));
    }
}
