<?php

namespace Zuber\Kit\Modules\Pages\Repositories;

use Illuminate\Support\Collection;
use Zuber\Kit\Modules\Pages\Enums\SharedBlockPositionEnum;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;

final readonly class SharedBlockRepository
{
    /**
     * @param  array<int, int>              $ids
     * @return Collection<int, SharedBlock>
     */
    public function findMany(array $ids): Collection
    {
        return $ids === [] ? new Collection : SharedBlock::query()->whereKey($ids)->get()->keyBy('id');
    }

    public function find(int $id): ?SharedBlock
    {
        return SharedBlock::query()->find($id);
    }

    /**
     * Все общие блоки со счётчиком страниц, где они стоят.
     *
     * @return Collection<int, SharedBlock>
     */
    public function list(): Collection
    {
        return SharedBlock::query()->withCount('pages')->orderBy('name')->get();
    }

    public function atPosition(SharedBlockPositionEnum $position): ?SharedBlock
    {
        return SharedBlock::query()->where('position', $position)->first();
    }

    /**
     * Общие блоки для выбора на странице: id => название.
     *
     * @return array<int, string>
     */
    public function options(): array
    {
        /** @var array<int, string> */
        return SharedBlock::query()->whereNull('position')->orderBy('name')->pluck('name', 'id')->all();
    }

    public function usages(SharedBlock $block): int
    {
        return $block->pages()->count();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): SharedBlock
    {
        return SharedBlock::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(SharedBlock $block, array $attributes): SharedBlock
    {
        $block->update($attributes);

        return $block;
    }

    public function referencesFile(string $path): bool
    {
        return SharedBlock::query()->where('data', 'like', '%' . PageRepository::jsonNeedle($path) . '%')->exists();
    }
}
