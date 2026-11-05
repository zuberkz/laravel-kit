<?php

namespace Zuber\Kit\Pages\Actions;

use Illuminate\Database\ConnectionInterface;
use Zuber\Kit\Pages\Models\Page;
use Zuber\Kit\Pages\Services\PageService;

/**
 * Копия страницы — черновик, чтобы собрать похожую страницу без набора блоков заново.
 */
final readonly class DuplicatePageAction
{
    public function __construct(
        private ConnectionInterface $db,
        private PageService $pages,
    ) {}

    public function handle(Page $page): Page
    {
        return $this->db->transaction(fn (): Page => $this->pages->duplicate($page));
    }
}
