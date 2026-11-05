<?php

namespace Zuber\Kit\Modules\Pages\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Zuber\Kit\Modules\Pages\Actions\SavePageAction;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Modules\Pages\Services\PageService;

final class UpdatePageTool extends PagesTool
{
    protected string $name = 'update-page';

    protected string $description = 'Настройки страницы: название, адрес, статус, SEO, шапка и подвал. Меняется только переданное. status=published — только по просьбе пользователя. Блоки — add-block, update-block, set-blocks.';

    public function __construct(
        private readonly PageService $pages,
        private readonly SavePageAction $savePage,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->description('id страницы из list-pages')->required(),
            'title' => $schema->string(),
            'path' => $schema->string()->description('Адрес: /services/cleaning; главная — /'),
            'status' => $schema->string()->enum(['draft', 'published']),
            'seo_title' => $schema->string()->description('Title до 60 символов; пустая строка — из названия'),
            'seo_description' => $schema->string()->description('Description 140–160 символов'),
            'show_header' => $schema->boolean(),
            'show_footer' => $schema->boolean(),
            'noindex' => $schema->boolean(),
        ];
    }

    protected function respond(Request $request): ResponseFactory
    {
        $input = $request->validate([
            'page_id' => ['required', 'integer'],
            'title' => ['sometimes', 'string', 'max:255'],
            'path' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(PageStatusEnum::class)],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:70'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:160'],
            'show_header' => ['sometimes', 'boolean'],
            'show_footer' => ['sometimes', 'boolean'],
            'noindex' => ['sometimes', 'boolean'],
        ]);

        $page = $this->pages->findOrFail($request->integer('page_id'));
        $data = PageData::fromPage($page, Arr::except($input, ['page_id']));

        if ($data->path !== $page->path) {
            $this->pages->assertPathAvailable($data->path, $page);
        }

        return Response::structured($this->page($this->savePage->handle($data, $page)));
    }
}
