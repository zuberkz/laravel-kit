<?php

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Zuber\Kit\Pages\Actions\SavePageAction;
use Zuber\Kit\Pages\Data\PageData;
use Zuber\Kit\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Pages\Filament\Resources\Pages\Pages\CreatePage;
use Zuber\Kit\Pages\Filament\Resources\Pages\Pages\EditPage;
use Zuber\Kit\Pages\Filament\Resources\Pages\Pages\ListPages;
use Zuber\Kit\Pages\Filament\Resources\SharedBlocks\Pages\ListSharedBlocks;
use Zuber\Kit\Pages\Models\Page;
use Zuber\Kit\Pages\Models\SharedBlock;
use Zuber\Kit\Tests\Fixtures\RoleEnum;

beforeEach(function (): void {
    $this->seedRoles();
    Filament::setCurrentPanel('panel');
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));
});

it('creates a page with blocks and links the shared blocks it uses', function (): void {
    $shared = SharedBlock::query()->create(['name' => 'Форма', 'type' => 'faq', 'data' => []]);
    $undoFake = Builder::fake();

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Уборка офисов',
            'path' => '/offices',
            'status' => PageStatusEnum::PUBLISHED->value,
            'blocks' => [
                ['type' => 'hero', 'data' => ['title' => 'Уборка офисов']],
                ['type' => 'shared', 'data' => ['shared_block_id' => $shared->id]],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoFake();
    $page = Page::query()->where('path', '/offices')->firstOrFail();

    expect($page->blocks)->toHaveCount(2)
        ->and($page->sharedBlocks->pluck('id')->all())->toBe([$shared->id]);
    $this->get('/offices')->assertOk()->assertSee('Уборка офисов');
});

it('suggests the address from the title', function (): void {
    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Uborka ofisov'])
        ->assertSchemaStateSet(['path' => '/uborka-ofisov']);
});

it('refuses an address that is malformed, taken or used by the site', function (string $path, ?string $rule): void {
    Page::query()->create(['title' => 'Занято', 'path' => '/taken', 'status' => PageStatusEnum::DRAFT]);

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'Страница', 'path' => $path, 'status' => PageStatusEnum::DRAFT->value])
        ->call('create')
        ->assertHasFormErrors($rule === null ? ['path'] : ['path' => $rule]);
})->with([
    'capital letters' => ['/Uborka', 'regex'],
    'taken by a page' => ['/taken', 'unique'],
    'route of the site' => ['/robots.txt', 'regex'],
    'admin panel' => ['/panel', null],
]);

it('copies a shared block into the page on detach', function (): void {
    $shared = SharedBlock::query()->create(['name' => 'Вопросы', 'type' => 'faq', 'data' => ['title' => 'Общие вопросы']]);
    $page = Page::query()->create(['title' => 'Стр', 'path' => '/s', 'status' => PageStatusEnum::DRAFT, 'blocks' => [
        ['type' => 'shared', 'data' => ['shared_block_id' => $shared->id, 'anchor' => 'faq']],
    ]]);
    $undoFake = Builder::fake();

    $component = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction(TestAction::make('detachSharedBlock')->schemaComponent('blocks', 'form')->arguments(['item' => '0']))
        ->call('save')
        ->assertHasNoFormErrors();

    $undoFake();

    $block = $page->refresh()->blocks[0] ?? [];

    expect($block['type'] ?? null)->toBe('faq')
        ->and($block['data'] ?? [])->toMatchArray(['title' => 'Общие вопросы', 'anchor' => 'faq'])
        ->and($page->sharedBlocks)->toBeEmpty();
});

it('duplicates a page as a draft with a free address', function (): void {
    $page = Page::query()->create(['title' => 'Уборка', 'path' => '/cleaning', 'status' => PageStatusEnum::PUBLISHED, 'blocks' => [['type' => 'hero', 'data' => ['title' => 'X']]]]);
    Page::query()->create(['title' => 'Копия', 'path' => '/cleaning-copy', 'status' => PageStatusEnum::DRAFT]);

    Livewire::test(ListPages::class)->callAction(TestAction::make('duplicate')->table($page));

    $copy = Page::query()->where('path', '/cleaning-copy-2')->firstOrFail();
    expect($copy->status)->toBe(PageStatusEnum::DRAFT)->and($copy->blocks)->toBe($page->blocks);
});

it('does not delete a shared block that pages use', function (): void {
    $used = SharedBlock::query()->create(['name' => 'Используется', 'type' => 'faq', 'data' => []]);
    $free = SharedBlock::query()->create(['name' => 'Свободный', 'type' => 'faq', 'data' => []]);
    Page::query()->create(['title' => 'Стр', 'path' => '/u', 'status' => PageStatusEnum::DRAFT])->sharedBlocks()->attach($used);

    Livewire::test(ListSharedBlocks::class)
        ->assertActionHidden(TestAction::make('delete')->table($used))
        ->assertActionVisible(TestAction::make('delete')->table($free));
});

it('deletes an image removed from the page unless another page still uses it', function (): void {
    Storage::fake('public');
    $old = UploadedFile::fake()->create('old.jpg', 10, 'image/jpeg')->store('blocks', 'public');
    $shared = UploadedFile::fake()->create('shared.jpg', 10, 'image/jpeg')->store('blocks', 'public');
    $save = app(SavePageAction::class);

    $page = $save->handle(new PageData('Стр', '/img', PageStatusEnum::DRAFT, [
        ['type' => 'hero', 'data' => ['title' => 'X', 'image' => $old]],
        ['type' => 'hero', 'data' => ['title' => 'Y', 'image' => $shared]],
    ]));
    $save->handle(new PageData('Копия', '/img-copy', PageStatusEnum::DRAFT, [['type' => 'hero', 'data' => ['title' => 'Y', 'image' => $shared]]]));

    $save->handle(new PageData('Стр', '/img', PageStatusEnum::DRAFT, [['type' => 'hero', 'data' => ['title' => 'X']]]), $page);

    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($shared);
});
