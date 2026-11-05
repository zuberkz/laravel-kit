<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Zuber\Kit\Modules\Pages\Services\BlockSchemaService;
use Zuber\Kit\Modules\Pages\Services\PageRenderService;

#[IsReadOnly]
final class ListBlockTypesTool extends PagesTool
{
    protected string $name = 'list-block-types';

    protected string $description = 'Типы блоков сайта: поля каждого (имя, тип, обязательность, подпись, подсказка, допустимые значения) и пример данных; настройки оформления любого блока; как поставить общий блок. Начинай работу со страницами с этого.';

    public function __construct(private readonly BlockSchemaService $schema) {}

    protected function respond(Request $request): Response
    {
        return Response::json([
            'types' => $this->schema->types(),
            'settings' => $this->schema->settings(),
            'shared' => [
                'type' => PageRenderService::SHARED_TYPE,
                'data' => ['shared_block_id' => 'id из list-shared-blocks'],
                'hint' => 'Общий блок правится один раз (update-shared-block) и меняется на всех страницах, где стоит; к нему можно добавить настройки оформления.',
            ],
        ]);
    }
}
