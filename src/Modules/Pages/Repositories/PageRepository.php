<?php

namespace Zuber\Kit\Modules\Pages\Repositories;

use Illuminate\Support\Collection;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Modules\Pages\Models\Page;

final readonly class PageRepository
{
    public function find(int $id): ?Page
    {
        return Page::query()->find($id);
    }

    /**
     * Все страницы для списка: без блоков, по адресу.
     *
     * @return Collection<int, Page>
     */
    public function list(): Collection
    {
        return Page::query()
            ->select(['id', 'title', 'path', 'status', 'noindex', 'updated_at'])
            ->orderBy('path')
            ->get();
    }

    public function findPublished(string $path): ?Page
    {
        return Page::query()->where('path', $path)->where('status', PageStatusEnum::PUBLISHED)->first();
    }

    /**
     * Опубликованные страницы для sitemap.xml, без закрытых от индексации.
     *
     * @return Collection<int, Page>
     */
    public function publishedForSitemap(): Collection
    {
        return Page::query()
            ->select(['id', 'path', 'updated_at'])
            ->where('status', PageStatusEnum::PUBLISHED)
            ->where('noindex', false)
            ->orderBy('path')
            ->get();
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, int>      $sharedBlockIds
     */
    public function create(array $attributes, array $sharedBlockIds): Page
    {
        $page = Page::query()->create($attributes);
        $page->sharedBlocks()->sync($sharedBlockIds);

        return $page;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, int>      $sharedBlockIds
     */
    public function update(Page $page, array $attributes, array $sharedBlockIds): Page
    {
        $page->update($attributes);
        $page->sharedBlocks()->sync($sharedBlockIds);

        return $page;
    }

    /**
     * @param int|null $exceptId страница, которой адрес принадлежит сейчас
     */
    public function pathExists(string $path, ?int $exceptId = null): bool
    {
        return Page::query()
            ->where('path', $path)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    /**
     * Файл картинки упомянут в блоках какой-нибудь страницы.
     */
    public function referencesFile(string $path): bool
    {
        return Page::query()
            ->where(fn ($query) => $query->where('blocks', 'like', '%' . self::jsonNeedle($path) . '%')->orWhere('og_image', $path))
            ->exists();
    }

    /**
     * Путь так, как он лежит в JSON: в кавычках и с экранированными слешами.
     */
    public static function jsonNeedle(string $path): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], (string) json_encode($path));
    }
}
