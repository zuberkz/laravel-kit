<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Zuber\Kit\Modules\Pages\Data\SharedBlockData;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\Repositories\SharedBlockRepository;

final readonly class SharedBlockService
{
    public function __construct(private SharedBlockRepository $sharedBlocks) {}

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
