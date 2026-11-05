<?php

namespace Zuber\Kit\Modules\Pages\Mcp;

use Laravel\Mcp\Server;
use Zuber\Kit\Modules\Pages\Mcp\Tools\AddBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\CreatePageTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\DeleteBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\GetPageTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ImageUploadUrlTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ListBlockTypesTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ListPagesTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\ListSharedBlocksTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\MoveBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\SetBlocksTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UpdateBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UpdatePageTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UpdateSharedBlockTool;
use Zuber\Kit\Modules\Pages\Mcp\Tools\UploadImageTool;

/**
 * MCP-сервер конструктора страниц: собрать и править лендинги из блоков проекта без админки.
 * Маршрут и доступ подключает проект (routes/ai.php) — OAuth и право на страницы:
 *
 *     Mcp::oauthRoutes();
 *     Mcp::web('/mcp/pages', PagesServer::class)
 *         ->middleware(['auth:api', 'can:' . PermissionEnum::MANAGE_PAGES->value, 'throttle:60,1']);
 *
 * Свои инструменты сайта — в наследнике: protected array $tools = [...PagesServer::TOOLS, OwnTool::class].
 */
class PagesServer extends Server
{
    /**
     * @var array<int, class-string<Server\Tool>>
     */
    public const array TOOLS = [
        ListBlockTypesTool::class,
        ListPagesTool::class,
        GetPageTool::class,
        CreatePageTool::class,
        UpdatePageTool::class,
        SetBlocksTool::class,
        AddBlockTool::class,
        UpdateBlockTool::class,
        MoveBlockTool::class,
        DeleteBlockTool::class,
        ListSharedBlocksTool::class,
        UpdateSharedBlockTool::class,
        UploadImageTool::class,
        ImageUploadUrlTool::class,
    ];

    protected string $name = 'Конструктор страниц';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        Конструктор страниц сайта: страница — список блоков, каждый блок {type, data}. Язык сайта — русский.

        Порядок работы:
        1. list-block-types — какие блоки есть, их поля, подсказки и пример данных. Поля и типы бери только отсюда.
        2. list-pages → get-page — что уже есть; номера блоков (index) — с 0.
        3. Новая страница: create-page (черновик), блоки сразу в blocks или потом add-block / set-blocks.
        4. Проверка: preview_url из ответа — предпросмотр черновика. Опубликовать (update-page status=published) — только по просьбе пользователя.

        Блоки:
        - data — поля блока из list-block-types и оформление: anchor (якорь для ссылок #anchor), hidden, background, padding_top, padding_bottom; пусто — как задано в блоке.
        - Точечные правки: add-block, update-block (меняются только переданные поля), move-block, delete-block. После вставки и удаления номера сдвигаются — перечитай get-page.
        - Общий блок (повторяется на многих страницах): {type: "shared", data: {shared_block_id}}; его содержимое правится один раз — update-shared-block. Шапка и подвал сайта — тоже общие блоки.
        - Картинки: по https-ссылке — upload-image; файлом (фото из чата, файл с диска) — image-upload-url и curl из ответа в песочнице. В обоих случаях в ответе path — его ставь в поле картинки блока.
        - Кнопки: #form — форма заявки во всплывающем окне, #form-3 — форма № 3, #anchor — к блоку с этим якорем, tel:+7… — звонок.

        Правила:
        - Тексты — по существу, без выдуманных фактов, цен, отзывов и клиентов: бери их у пользователя.
        - SEO: seo_title до 60 символов с главным запросом в начале, seo_description 140–160 символов с выгодой и призывом.
        - set-blocks заменяет все блоки, delete-block удаляет — только по просьбе пользователя; скрыть блок можно hidden = true.
        - После правки дай пользователю ссылку: url страницы или preview_url черновика.
        MARKDOWN;

    /**
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = self::TOOLS;
}
