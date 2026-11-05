<?php

namespace Zuber\Kit\Modules\Pages\Exceptions;

use Zuber\Kit\Core\Exceptions\DomainException;

/**
 * Отказ конструктора страниц при правке не из админки (MCP): блок не собрать, адрес занят, нет такой
 * страницы или блока. Сообщение — для того, кто правит, с подсказкой, что исправить.
 */
final class PageBuilderException extends DomainException
{
    public static function pageNotFound(int $id): self
    {
        return new self(__('Страницы № :id нет. Список страниц — list-pages.', ['id' => $id]));
    }

    public static function sharedBlockNotFound(int $id): self
    {
        return new self(__('Общего блока № :id нет. Список — list-shared-blocks.', ['id' => $id]));
    }

    public static function unknownType(string $type): self
    {
        return new self(__('Блока «:type» нет. Типы блоков и их поля — list-block-types.', ['type' => $type]));
    }

    public static function invalidBlock(int $index, string $type, string $reason): self
    {
        return new self(__('Блок № :index («:type»): :reason', ['index' => $index, 'type' => $type, 'reason' => $reason]));
    }

    public static function invalidSetting(int $index, string $setting, string $allowed): self
    {
        return new self(__('Блок № :index: у настройки :setting допустимы значения :allowed.', ['index' => $index, 'setting' => $setting, 'allowed' => $allowed]));
    }

    public static function indexOutOfRange(int $index, int $count): self
    {
        return new self(__('Блока № :index нет: на странице :count блоков, номера с 0.', ['index' => $index, 'count' => $count]));
    }

    public static function invalidPath(string $path): self
    {
        return new self(__('Адрес «:path» не подходит: латиница в нижнем регистре, цифры и дефисы — /services/cleaning, главная — /.', ['path' => $path]));
    }

    public static function pathTaken(string $path): self
    {
        return new self(__('Адрес «:path» уже занят другой страницей.', ['path' => $path]));
    }

    public static function pathTakenByRoute(string $path): self
    {
        return new self(__('Адрес «:path» занят разделом сайта.', ['path' => $path]));
    }

    public static function imageRejected(string $reason): self
    {
        return new self(__('Картинку не загрузить: :reason', ['reason' => $reason]));
    }
}
