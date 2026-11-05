<?php

namespace Zuber\Kit\Pages\Repositories;

use Illuminate\Support\Collection;
use Zuber\Kit\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Pages\Models\Page;

final readonly class PageRepository
{
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

    public function pathExists(string $path): bool
    {
        return Page::query()->where('path', $path)->exists();
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
