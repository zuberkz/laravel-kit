<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Zuber\Kit\Modules\Pages\Actions\SavePageAction;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Services\PageBlocksService;
use Zuber\Kit\Modules\Pages\Services\PageService;

#[IsDestructive]
final class SetBlocksTool extends PagesTool
{
    protected string $name = 'set-blocks';

    protected string $description = 'Заменить все блоки страницы списком [{type, data}] — собрать страницу целиком. Прежние блоки удаляются: для точечных правок — add-block, update-block, move-block, delete-block.';

    public function __construct(
        private readonly PageService $pages,
        private readonly PageBlocksService $blocks,
        private readonly SavePageAction $savePage,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->description('id страницы из list-pages')->required(),
            'blocks' => $schema->array()->items($schema->object())->description('Блоки [{type, data}] по порядку')->required(),
        ];
    }

    /**
     * @throws \JsonException
     */
    protected function respond(Request $request): Response
    {
        $input = $request->validate(['page_id' => ['required', 'integer'], 'blocks' => ['present', 'array']]);

        $page = $this->pages->findOrFail($request->integer('page_id'));
        $blocks = $this->blocks->normalize(is_array($input['blocks']) ? $input['blocks'] : []);

        return Response::json($this->page($this->savePage->handle(PageData::fromPage($page, ['blocks' => $blocks]), $page)));
    }
}
