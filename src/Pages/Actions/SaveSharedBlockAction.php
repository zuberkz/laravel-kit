<?php

namespace Zuber\Kit\Pages\Actions;

use Illuminate\Database\ConnectionInterface;
use Zuber\Kit\Pages\Data\SharedBlockData;
use Zuber\Kit\Pages\Models\SharedBlock;
use Zuber\Kit\Pages\Services\BlockImageService;
use Zuber\Kit\Pages\Services\SharedBlockService;

/**
 * Администратор сохраняет общий блок; изменения сразу видны на всех страницах с этим блоком.
 */
final readonly class SaveSharedBlockAction
{
    public function __construct(
        private ConnectionInterface $db,
        private SharedBlockService $sharedBlocks,
        private BlockImageService $images,
    ) {}

    public function handle(SharedBlockData $data, ?SharedBlock $block = null): SharedBlock
    {
        $before = $block === null ? [] : $block->data;

        $saved = $this->db->transaction(fn (): SharedBlock => $block === null
            ? $this->sharedBlocks->create($data)
            : $this->sharedBlocks->update($block, $data));

        $this->images->deleteUnused($before, $data->data);

        return $saved;
    }
}
