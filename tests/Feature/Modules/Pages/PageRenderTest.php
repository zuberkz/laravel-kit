<?php

use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Modules\Pages\Enums\SharedBlockPositionEnum;
use Zuber\Kit\Modules\Pages\Exceptions\BlockException;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\Services\BlockRegistryService;
use Zuber\Kit\Modules\Pages\Services\PageRenderService;
use Zuber\Kit\Modules\Pages\Support\PagePreviewSupport;

/**
 * @param array<int, array{type: string, data: array<string, mixed>}> $blocks
 * @param array<string, mixed>                                        $attributes
 */
function page(string $path, array $blocks, array $attributes = []): Page
{
    return Page::query()->create([
        'title' => 'Уборка',
        'path' => $path,
        'status' => PageStatusEnum::PUBLISHED,
        'blocks' => $blocks,
        ...$attributes,
    ]);
}

it('renders the blocks of a published page in order inside the site layout', function (): void {
    page('/cleaning', [
        ['type' => 'hero', 'data' => ['title' => 'Уборка квартир', 'text' => 'За один день']],
        ['type' => 'faq', 'data' => ['items' => [['question' => 'Сколько?', 'answer' => 'От 15 000 ₸']]]],
    ], ['seo_title' => 'Уборка квартир в Алматы']);

    $this->get('/cleaning')
        ->assertOk()
        ->assertSee('<title>Уборка квартир в Алматы</title>', false)
        ->assertSeeInOrder(['Уборка квартир', 'За один день', 'Вопросы и ответы', 'Сколько? — От 15 000 ₸']);
});

it('serves the home page at /', function (): void {
    page(Page::HOME_PATH, [['type' => 'hero', 'data' => ['title' => 'Главная']]]);

    $this->get('/')->assertOk()->assertSee('Главная');
});

it('answers 404 for a draft and for an unknown address', function (): void {
    page('/draft', [], ['status' => PageStatusEnum::DRAFT]);

    $this->get('/draft')->assertNotFound();
    $this->get('/nothing-here')->assertNotFound();
});

it('lets routes of the site win over pages, wherever they are declared', function (): void {
    Route::get('/catalog', fn (): string => 'каталог сайта');
    page('/catalog', [['type' => 'hero', 'data' => ['title' => 'Страница конструктора']]]);

    $this->get('/catalog')->assertOk()->assertSee('каталог сайта')->assertDontSee('Страница конструктора');
});

it('skips hidden blocks and gives a block its anchor', function (): void {
    page('/prices', [
        ['type' => 'hero', 'data' => ['title' => 'Скрытый', 'hidden' => true]],
        ['type' => 'faq', 'data' => ['title' => 'Цены', 'anchor' => 'prices']],
    ]);

    $this->get('/prices')->assertDontSee('Скрытый')->assertSee('id="prices"', false);
});

it('gives a block the background and spacing of its class unless the page overrides them', function (): void {
    $form = SharedBlock::query()->create(['name' => 'Вопросы', 'type' => 'faq', 'data' => ['title' => 'Общие']]);
    page('/look', [
        ['type' => 'faq', 'data' => ['title' => 'По умолчанию']],
        ['type' => 'hero', 'data' => ['title' => 'Свой фон', 'background' => 'inverse', 'padding_top' => 'none', 'padding_bottom' => 'lg']],
        ['type' => 'shared', 'data' => ['shared_block_id' => $form->id, 'background' => 'brand']],
    ]);

    $this->get('/look')->assertSeeInOrder([
        'block-bg-surface block-pt-md block-pb-md', 'По умолчанию',
        'block-bg-inverse block-pt-none block-pb-lg', 'Свой фон',
        'block-bg-brand block-pt-md block-pb-md', 'Общие',
    ], false);
});

it('renders shared blocks, the site header and footer, and lets a page switch them off', function (): void {
    $form = SharedBlock::query()->create(['name' => 'Вопросы', 'type' => 'faq', 'data' => ['title' => 'Общие вопросы']]);
    SharedBlock::query()->create(['name' => 'Шапка', 'type' => 'hero', 'data' => ['title' => 'Шапка сайта'], 'position' => SharedBlockPositionEnum::HEADER]);
    SharedBlock::query()->create(['name' => 'Подвал', 'type' => 'hero', 'data' => ['title' => 'Подвал сайта'], 'position' => SharedBlockPositionEnum::FOOTER]);

    page('/a', [['type' => 'shared', 'data' => ['shared_block_id' => $form->id, 'anchor' => 'questions']]]);
    page('/promo', [], ['show_header' => false, 'show_footer' => false]);

    $this->get('/a')->assertSeeInOrder(['Шапка сайта', 'id="questions"', 'Общие вопросы', 'Подвал сайта'], false);
    $this->get('/promo')->assertDontSee('Шапка сайта')->assertDontSee('Подвал сайта');
});

it('shows the page without a block it cannot build and reports it', function (array $item): void {
    Exceptions::fake();
    page('/broken', [$item, ['type' => 'hero', 'data' => ['title' => 'Живой блок']]]);

    $this->get('/broken')->assertOk()->assertSee('Живой блок');

    Exceptions::assertReported(BlockException::class);
})->with([
    'type removed from the code' => [['type' => 'gallery', 'data' => []]],
    'required field missing' => [['type' => 'hero', 'data' => ['text' => 'без заголовка']]],
    'shared block deleted' => [['type' => 'shared', 'data' => ['shared_block_id' => 999]]],
]);

it('previews a draft by a signed link, closed from search engines', function (): void {
    $draft = page('/draft', [['type' => 'hero', 'data' => ['title' => 'Черновик']]], ['status' => PageStatusEnum::DRAFT]);

    $this->get(PagePreviewSupport::url($draft))
        ->assertOk()
        ->assertSee('Черновик')
        ->assertSee('<meta name="robots" content="noindex">', false);

    $this->get(route('kit.pages.preview', ['page' => $draft]))->assertForbidden();
});

it('lists published indexable pages in the sitemap', function (): void {
    config(['app.url' => 'https://example.kz']);
    page('/open', []);
    page('/hidden', [], ['noindex' => true]);
    page('/draft', [], ['status' => PageStatusEnum::DRAFT]);

    $this->get('/sitemap.xml')
        ->assertSee('<loc>https://example.kz/open</loc>', false)
        ->assertDontSee('/hidden')
        ->assertDontSee('/draft');
});

it('renders every block from its example', function (): void {
    $renderer = app(PageRenderService::class);

    collect(app(BlockRegistryService::class)->all())->each(function (string $class, string $type) use ($renderer): void {
        $html = view('kit::components.blocks', ['blocks' => [$renderer->block($type, $class::example())]])->render();

        expect(trim($html))->not->toBeEmpty($type);
    });
});
