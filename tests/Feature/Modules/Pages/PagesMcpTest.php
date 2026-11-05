<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Zuber\Kit\Core\Setup\ExceptionsSetup;
use Zuber\Kit\Modules\Pages\Actions\CreateImageUploadLinkAction;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Modules\Pages\Enums\SharedBlockPositionEnum;
use Zuber\Kit\Modules\Pages\Mcp\PagesServer;
use Zuber\Kit\Modules\Pages\Mcp\Tools\AddBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\CreatePageTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\DeleteBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\GetPageTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ImageUploadUrlTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ListBlockTypesTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ListPagesTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ListSharedBlocksTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\MoveBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\SetBlocksTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UpdateBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UpdatePageTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UpdateSharedBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UploadImageTool;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Tests\Fixtures\RoleEnum;

beforeEach(function (): void {
    // Отказы показывает обработка исключений кита — в проекте её подключает bootstrap/app.php
    $setup = new ExceptionsSetup;
    $setup(new Exceptions(app(ExceptionHandler::class)));

    $this->seedRoles();
    $this->admin = $this->userWithRole(RoleEnum::ADMIN);
});

function mcpPng(): string
{
    return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true);
}

/**
 * @param array<int, array<string, mixed>> $blocks
 */
function mcpPage(array $blocks = [], string $path = '/landing'): Page
{
    return Page::query()->create(['title' => 'Лендинг', 'path' => $path, 'status' => PageStatusEnum::DRAFT, 'blocks' => $blocks]);
}

/**
 * @return array<int, array<string, mixed>>
 */
function mcpBlocks(Page $page): array
{
    return $page->fresh()?->blocks ?? [];
}

it('offers every page builder tool under its name', function (): void {
    // assertRegistered создаёт инструменты без контейнера, а у них зависимости в конструкторе
    PagesServer::actingAs($this->admin)->tools();

    expect(collect(PagesServer::TOOLS)->map(fn (string $tool): string => app($tool)->name())->all())->toBe([
        'list-block-types', 'list-pages', 'get-page', 'create-page', 'update-page', 'set-blocks', 'add-block',
        'update-block', 'move-block', 'delete-block', 'list-shared-blocks', 'update-shared-block', 'upload-image',
        'image-upload-url',
    ]);
});

it('describes the block types of the project with their fields and the shared block', function (): void {
    PagesServer::actingAs($this->admin)->tool(ListBlockTypesTool::class)
        ->assertOk()
        ->assertSee(['"type":"hero"', '"name":"title","type":"string","required":true', '"name":"text","type":"string"', '"shared_block_id"', '"padding_top"'])
        ->assertSee('Уборка квартир в Алматы');
});

it('creates a draft page with checked blocks and links the shared blocks', function (): void {
    $shared = SharedBlock::query()->create(['name' => 'Вопросы', 'type' => 'faq', 'data' => []]);

    PagesServer::actingAs($this->admin)->tool(CreatePageTool::class, [
        'title' => 'Уборка офисов',
        'path' => 'Offices/',
        'blocks' => [
            ['type' => 'hero', 'data' => ['title' => 'Уборка офисов', 'background' => 'brand', 'anchor' => 'top']],
            ['type' => 'shared', 'data' => ['shared_block_id' => $shared->id]],
        ],
    ])->assertOk()->assertSee(['"path":"/offices"', '"status":"draft"', 'preview_url']);

    $page = Page::query()->where('path', '/offices')->sole();

    expect($page->status)->toBe(PageStatusEnum::DRAFT)
        ->and($page->blocks)->toBe([
            ['type' => 'hero', 'data' => ['title' => 'Уборка офисов', 'background' => 'brand', 'anchor' => 'top']],
            ['type' => 'shared', 'data' => ['shared_block_id' => $shared->id]],
        ])
        ->and($page->sharedBlocks()->pluck('shared_blocks.id')->all())->toBe([$shared->id]);
});

it('refuses a page that could not be built', function (array $arguments, string $error): void {
    mcpPage(path: '/taken');

    PagesServer::actingAs($this->admin)->tool(CreatePageTool::class, ['title' => 'Страница', 'path' => '/new', ...$arguments])
        ->assertHasErrors([$error]);

    expect(Page::query()->where('path', '/new')->exists())->toBeFalse();
})->with([
    'path of another page' => [['path' => '/taken'], 'уже занят другой страницей'],
    'path of a site route' => [['path' => '/panel'], 'занят разделом сайта'],
    'path with spaces' => [['path' => '/уборка офисов'], 'не подходит'],
    'unknown block type' => [['blocks' => [['type' => 'gallery', 'data' => []]]], 'Блока «gallery» нет'],
    'missing required field' => [['blocks' => [['type' => 'hero', 'data' => ['text' => 'Без заголовка']]]], 'не заполнены обязательные поля'],
    'field the block does not have' => [['blocks' => [['type' => 'hero', 'data' => ['title' => 'Да', 'subtitle' => 'Нет']]]], 'нет полей subtitle'],
    'value of a wrong type' => [['blocks' => [['type' => 'hero', 'data' => ['title' => ['список']]]]], 'не того типа'],
    'unknown background' => [['blocks' => [['type' => 'hero', 'data' => ['title' => 'Да', 'background' => 'red']]]], 'у настройки background'],
    'missing shared block' => [['blocks' => [['type' => 'shared', 'data' => ['shared_block_id' => 999]]]], 'Общего блока № 999 нет'],
]);

it('lists pages and shows a page with numbered blocks', function (): void {
    $page = mcpPage([['type' => 'hero', 'data' => ['title' => 'Первый экран']]]);

    PagesServer::actingAs($this->admin)->tool(ListPagesTool::class)->assertOk()->assertSee(['"id":' . $page->id, '"path":"/landing"']);
    PagesServer::actingAs($this->admin)->tool(GetPageTool::class, ['page_id' => $page->id])
        ->assertOk()
        ->assertSee(['"index":0,"type":"hero"', 'Первый экран', '_kit/pages/' . $page->id . '/preview']);
    PagesServer::actingAs($this->admin)->tool(GetPageTool::class, ['page_id' => 999])->assertHasErrors(['Страницы № 999 нет']);
});

it('adds, changes, moves and deletes blocks one by one', function (): void {
    $page = mcpPage([['type' => 'hero', 'data' => ['title' => 'Первый экран', 'text' => 'Пояснение']]]);
    $server = PagesServer::actingAs($this->admin);

    $server->tool(AddBlockTool::class, ['page_id' => $page->id, 'type' => 'faq', 'data' => ['title' => 'Вопросы']])->assertOk();
    $server->tool(AddBlockTool::class, ['page_id' => $page->id, 'type' => 'hero', 'data' => ['title' => 'Второй'], 'position' => 0])->assertOk();
    expect(collect(mcpBlocks($page))->pluck('data.title')->all())->toBe(['Второй', 'Первый экран', 'Вопросы']);

    $server->tool(UpdateBlockTool::class, ['page_id' => $page->id, 'index' => 1, 'data' => ['title' => 'Новый', 'text' => null]])->assertOk();
    expect(mcpBlocks($page)[1])->toBe(['type' => 'hero', 'data' => ['title' => 'Новый']]);

    $server->tool(MoveBlockTool::class, ['page_id' => $page->id, 'from' => 2, 'to' => 0])->assertOk();
    $server->tool(DeleteBlockTool::class, ['page_id' => $page->id, 'index' => 2])->assertOk();
    expect(collect(mcpBlocks($page))->pluck('data.title')->all())->toBe(['Вопросы', 'Второй']);

    $server->tool(DeleteBlockTool::class, ['page_id' => $page->id, 'index' => 5])->assertHasErrors(['Блока № 5 нет']);
    $server->tool(UpdateBlockTool::class, ['page_id' => $page->id, 'index' => 1, 'data' => ['title' => null]])->assertHasErrors(['не заполнены обязательные поля']);
    expect(mcpBlocks($page))->toHaveCount(2);
});

it('replaces all blocks of a page at once', function (): void {
    $page = mcpPage([['type' => 'hero', 'data' => ['title' => 'Старый']]]);

    PagesServer::actingAs($this->admin)->tool(SetBlocksTool::class, [
        'page_id' => $page->id,
        'blocks' => [['type' => 'faq', 'data' => []], ['type' => 'hero', 'data' => ['title' => 'Новый', 'hidden' => true]]],
    ])->assertOk();

    expect(mcpBlocks($page))->toBe([
        ['type' => 'faq', 'data' => []],
        ['type' => 'hero', 'data' => ['title' => 'Новый', 'hidden' => true]],
    ]);
});

it('changes only the page settings it was given', function (): void {
    $page = mcpPage([['type' => 'hero', 'data' => ['title' => 'Первый экран']]]);
    mcpPage(path: '/taken');

    PagesServer::actingAs($this->admin)->tool(UpdatePageTool::class, [
        'page_id' => $page->id,
        'status' => 'published',
        'seo_title' => 'Уборка офисов в Алматы',
    ])->assertOk()->assertSee(['"status":"published"', '"preview_url":null']);

    $page->refresh();
    expect($page->status)->toBe(PageStatusEnum::PUBLISHED)
        ->and($page->seo_title)->toBe('Уборка офисов в Алматы')
        ->and($page->title)->toBe('Лендинг')
        ->and($page->blocks)->toHaveCount(1);

    PagesServer::actingAs($this->admin)->tool(UpdatePageTool::class, ['page_id' => $page->id, 'path' => '/taken'])
        ->assertHasErrors(['уже занят другой страницей']);
    PagesServer::actingAs($this->admin)->tool(UpdatePageTool::class, ['page_id' => $page->id, 'status' => 'archived'])
        ->assertHasErrors(['status']);
});

it('changes a shared block on every page and keeps its position', function (): void {
    $header = SharedBlock::query()->create([
        'name' => 'Шапка',
        'type' => 'hero',
        'data' => ['title' => 'Старое название', 'text' => 'Слоган'],
        'position' => SharedBlockPositionEnum::HEADER,
    ]);

    PagesServer::actingAs($this->admin)->tool(ListSharedBlocksTool::class)->assertOk()->assertSee(['"position":"header"', 'Старое название']);
    PagesServer::actingAs($this->admin)->tool(UpdateSharedBlockTool::class, ['shared_block_id' => $header->id, 'data' => ['title' => 'Новое название']])
        ->assertOk();

    $header->refresh();
    expect($header->data)->toBe(['title' => 'Новое название', 'text' => 'Слоган'])
        ->and($header->position)->toBe(SharedBlockPositionEnum::HEADER);

    PagesServer::actingAs($this->admin)->tool(UpdateSharedBlockTool::class, ['shared_block_id' => $header->id, 'data' => ['logo' => 'x']])
        ->assertHasErrors(['нет полей logo']);
});

it('uploads an image by a public https link into the block images', function (): void {
    Storage::fake('public');
    Http::preventStrayRequests();
    Http::fake(['https://93.184.215.14/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true), 200, ['Content-Type' => 'image/png'])]);

    PagesServer::actingAs($this->admin)->tool(UploadImageTool::class, ['url' => 'https://93.184.215.14/photo.png'])
        ->assertOk()
        ->assertSee('"path":"blocks/');

    expect(Storage::disk('public')->allFiles('blocks'))->toHaveCount(1)
        ->and(Storage::disk('public')->allFiles('blocks')[0])->toEndWith('.png');
});

it('refuses images that are not public https pictures', function (string $url, string $error): void {
    Storage::fake('public');
    Http::preventStrayRequests();
    Http::fake(['https://93.184.215.14/*' => Http::response('<html>не картинка</html>', 200, ['Content-Type' => 'image/png'])]);

    PagesServer::actingAs($this->admin)->tool(UploadImageTool::class, ['url' => $url])->assertHasErrors([$error]);

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
})->with([
    'plain http' => ['http://93.184.215.14/photo.png', 'url'],
    'internal network' => ['https://10.0.0.5/photo.png', 'внутреннюю сеть'],
    'the site itself' => ['https://127.0.0.1/photo.png', 'внутреннюю сеть'],
    'not an image' => ['https://93.184.215.14/page.png', 'это не картинка'],
]);

it('gives a one-time link to send an image file with curl', function (): void {
    PagesServer::actingAs($this->admin)->tool(ImageUploadUrlTool::class)
        ->assertOk()
        ->assertSee(['_kit/pages/images/', 'signature=', "curl -sS -F 'file=@"]);
});

it('takes an image file by the one-time link only once', function (): void {
    Storage::fake('public');
    $url = app(CreateImageUploadLinkAction::class)->handle()->url;

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('photo.png', mcpPng())])
        ->assertCreated()
        ->assertJsonPath('path', fn (string $path): bool => str_starts_with($path, 'blocks/') && str_ends_with($path, '.png'));

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('photo.png', mcpPng())])
        ->assertUnprocessable()
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'уже использована'));

    expect(Storage::disk('public')->allFiles('blocks'))->toHaveCount(1);
});

it('keeps the link when the file is not an image', function (): void {
    Storage::fake('public');
    $url = app(CreateImageUploadLinkAction::class)->handle()->url;

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('page.png', '<html>не картинка</html>')])
        ->assertUnprocessable()
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'это не картинка'));
    $this->post($url, [])->assertUnprocessable()->assertJsonValidationErrors('file');

    $this->post($url, ['file' => UploadedFile::fake()->createWithContent('photo.png', mcpPng())])->assertCreated();
});

it('refuses a forged or expired upload link', function (): void {
    Storage::fake('public');
    $url = app(CreateImageUploadLinkAction::class)->handle()->url;
    $file = fn (): UploadedFile => UploadedFile::fake()->createWithContent('photo.png', mcpPng());

    $this->post(preg_replace('/signature=\w+/', 'signature=forged', $url) ?? '', ['file' => $file()])->assertForbidden();

    $this->travel(11)->minutes();
    $this->post($url, ['file' => $file()])->assertForbidden();

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});
