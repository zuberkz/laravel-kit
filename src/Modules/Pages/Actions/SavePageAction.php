<?php

namespace Zuber\Kit\Modules\Pages\Actions;

use Illuminate\Database\ConnectionInterface;
use Zuber\Kit\Modules\Pages\Data\PageData;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\Services\BlockImageService;
use Zuber\Kit\Modules\Pages\Services\PageService;

/**
 * Администратор сохраняет страницу (новую или существующую): страница и связи с общими блоками —
 * одной транзакцией, после неё удаляются картинки, которые со страницы убрали.
 */
final readonly class SavePageAction
{
    public function __construct(
        private ConnectionInterface $db,
        private PageService $pages,
        private BlockImageService $images,
    ) {}

    public function handle(PageData $data, ?Page $page = null): Page
    {
        $before = $page === null ? [] : [$page->blocks, $page->og_image];

        $saved = $this->db->transaction(fn (): Page => $page === null
            ? $this->pages->create($data)
            : $this->pages->update($page, $data));

        $this->images->deleteUnused($before, [$data->blocks, $data->ogImage]);

        return $saved;
    }
}
