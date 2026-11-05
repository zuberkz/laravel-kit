<?php

namespace Zuber\Kit\Pages\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Zuber\Kit\Pages\Data\PageViewData;
use Zuber\Kit\Pages\Data\RenderedBlockData;
use Zuber\Kit\Pages\Enums\BlockBackgroundEnum;
use Zuber\Kit\Pages\Enums\BlockSpacingEnum;
use Zuber\Kit\Pages\Enums\SharedBlockPositionEnum;
use Zuber\Kit\Pages\Exceptions\BlockException;
use Zuber\Kit\Pages\Models\Page;
use Zuber\Kit\Pages\Models\SharedBlock;
use Zuber\Kit\Pages\Repositories\SharedBlockRepository;

/**
 * Страница для вывода: общие блоки разворачиваются, скрытые пропускаются, блок, который не собрать
 * (тип удалён из кода, нет обязательного поля), уходит в лог, а страница показывается без него.
 */
final readonly class PageRenderService
{
    public const string SHARED_TYPE = 'shared';

    /**
     * «Оформление» блока на странице: кит добавляет эти поля к каждому блоку.
     */
    public const array SETTINGS = ['anchor', 'hidden', 'background', 'padding_top', 'padding_bottom'];

    public function __construct(
        private BlockRegistryService $registry,
        private SharedBlockRepository $sharedBlocks,
        private BlockImageService $images,
    ) {}

    /**
     * @param bool $preview черновик в предпросмотре: всегда noindex
     */
    public function render(Page $page, bool $preview = false): PageViewData
    {
        $items = collect($page->blocks ?? [])->values();
        $shared = $this->sharedBlocks->findMany(self::sharedIds($page->blocks ?? []));

        return new PageViewData(
            title: $page->seo_title ?: $page->title,
            description: $page->seo_description,
            ogImage: $this->images->url($page->og_image),
            noindex: $page->noindex || $preview,
            header: $page->show_header ? $this->positioned(SharedBlockPositionEnum::HEADER) : [],
            main: $items
                ->map(fn (mixed $item): ?RenderedBlockData => is_array($item) ? $this->item($item, $shared) : null)
                ->filter()
                ->values()
                ->all(),
            footer: $page->show_footer ? $this->positioned(SharedBlockPositionEnum::FOOTER) : [],
        );
    }

    /**
     * id общих блоков, на которые ссылаются блоки страницы.
     *
     * @param  array<int|string, mixed> $items
     * @return array<int, int>
     */
    public static function sharedIds(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && ($item['type'] ?? null) === self::SHARED_TYPE)
            ->map(fn (array $item): mixed => Arr::get($item, 'data.shared_block_id'))
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Один блок с данными — для шаблона и для предпросмотра в тестах.
     *
     * @param array<string, mixed> $data
     *
     * @throws BlockException
     */
    public function block(string $type, array $data): RenderedBlockData
    {
        $class = $this->registry->find($type) ?? throw BlockException::unknownType($type);
        $anchor = $data['anchor'] ?? null;

        return new RenderedBlockData(
            view: $class::view(),
            block: $class::fromArray($data),
            background: BlockBackgroundEnum::tryFrom(self::string($data['background'] ?? null)) ?? $class::background(),
            paddingTop: BlockSpacingEnum::tryFrom(self::string($data['padding_top'] ?? null)) ?? $class::spacing(),
            paddingBottom: BlockSpacingEnum::tryFrom(self::string($data['padding_bottom'] ?? null)) ?? $class::spacing(),
            anchor: is_string($anchor) && $anchor !== '' ? $anchor : null,
        );
    }

    /**
     * @param array<array-key, mixed>      $item
     * @param Collection<int, SharedBlock> $shared
     */
    private function item(array $item, Collection $shared): ?RenderedBlockData
    {
        $type = $item['type'] ?? null;
        $data = is_array($item['data'] ?? null) ? $item['data'] : [];

        if (! is_string($type) || ($data['hidden'] ?? false) === true) {
            return null;
        }

        try {
            if ($type !== self::SHARED_TYPE) {
                return $this->block($type, $data);
            }

            $id = is_numeric($data['shared_block_id'] ?? null) ? (int) $data['shared_block_id'] : 0;
            $block = $shared->get($id) ?? throw BlockException::sharedBlockMissing($id);

            // Якорь, фон и отступы — свои у каждой страницы, где стоит общий блок
            return $this->block($block->type, [...$block->data ?? [], ...Arr::only($data, self::SETTINGS)]);
        } catch (BlockException $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return array<int, RenderedBlockData>
     */
    private function positioned(SharedBlockPositionEnum $position): array
    {
        $block = $this->sharedBlocks->atPosition($position);

        if ($block === null) {
            return [];
        }

        try {
            return [$this->block($block->type, $block->data ?? [])];
        } catch (BlockException $exception) {
            report($exception);

            return [];
        }
    }

    private static function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
