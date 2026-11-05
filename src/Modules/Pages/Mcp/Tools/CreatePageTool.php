<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Zuber\Kit\Modules\Pages\Actions\SavePageAction;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Modules\Pages\Services\PageBlocksService;
use Zuber\Kit\Modules\Pages\Services\PageService;

final class CreatePageTool extends PagesTool
{
    protected string $name = 'create-page';

    protected string $description = 'Новая страница — черновиком, пока пользователь не попросил опубликовать. Блоки можно передать сразу ([{type, data}], поля — list-block-types) или добавить потом add-block. Ответ — страница с preview_url.';

    public function __construct(
        private readonly PageService $pages,
        private readonly PageBlocksService $blocks,
        private readonly SavePageAction $savePage,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Название страницы (в админке и заголовок по умолчанию)')->required(),
            'path' => $schema->string()->description('Адрес: /services/cleaning; главная — /')->required(),
            'status' => $schema->string()->enum(['draft', 'published'])->description('draft по умолчанию; published — только по просьбе пользователя'),
            'blocks' => $schema->array()->items($schema->object())->description('Блоки [{type, data}] по порядку'),
            'seo_title' => $schema->string()->description('Title до 60 символов, главный запрос в начале'),
            'seo_description' => $schema->string()->description('Description 140–160 символов: выгода и призыв'),
            'show_header' => $schema->boolean()->description('Показывать шапку сайта (по умолчанию да)'),
            'show_footer' => $schema->boolean()->description('Показывать подвал сайта (по умолчанию да)'),
            'noindex' => $schema->boolean()->description('Закрыть от поисковиков'),
        ];
    }

    protected function respond(Request $request): Response
    {
        $input = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PageStatusEnum::class)],
            'blocks' => ['nullable', 'array'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'show_header' => ['nullable', 'boolean'],
            'show_footer' => ['nullable', 'boolean'],
            'noindex' => ['nullable', 'boolean'],
        ]);

        $data = PageData::fromForm([
            ...$input,
            'status' => $input['status'] ?? PageStatusEnum::DRAFT->value,
            'blocks' => $this->blocks->normalize(is_array($input['blocks'] ?? null) ? $input['blocks'] : []),
        ]);
        $this->pages->assertPathAvailable($data->path);

        return Response::json($this->page($this->savePage->handle($data)));
    }
}
