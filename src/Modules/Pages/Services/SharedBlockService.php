<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Illuminate\Support\Collection;
use Zuber\Kit\Modules\Pages\Data\SharedBlockData;
use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\Repositories\SharedBlockRepository;

final readonly class SharedBlockService
{
    public function __construct(private SharedBlockRepository $sharedBlocks) {}

    /**
     * @return Collection<int, SharedBlock>
     */
    public function list(): Collection
    {
        return $this->sharedBlocks->list();
    }

    /**
     * @throws PageBuilderException
     */
    public function findOrFail(int $id): SharedBlock
    {
        return $this->sharedBlocks->find($id) ?? throw PageBuilderException::sharedBlockNotFound($id);
    }

    public function create(SharedBlockData $data): SharedBlock
    {
        return $this->sharedBlocks->create($data->attributes());
    }

    public function update(SharedBlock $block, SharedBlockData $data): SharedBlock
    {
        return $this->sharedBlocks->update($block, $data->attributes());
    }

    /**
     * Используемый блок не удаляют: страницы, которые на него ссылаются, потеряли бы раздел.
     */
    public function canDelete(SharedBlock $block): bool
    {
        return $block->position === null && $this->sharedBlocks->usages($block) === 0;
    }
}
