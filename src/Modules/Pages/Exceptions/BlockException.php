<?php

namespace Zuber\Kit\Modules\Pages\Exceptions;

use RuntimeException;

/**
 * Блок страницы не собрать: тип удалили из кода или в данных нет обязательного поля. Страница
 * показывается без этого блока, ошибка уходит в лог.
 */
final class BlockException extends RuntimeException
{
    public static function unknownType(string $type): self
    {
        return new self(sprintf('Block type [%s] has no class in app/Blocks.', $type));
    }

    public static function missingField(string $block, string $field): self
    {
        return new self(sprintf('Block [%s] has no value for required field [%s].', $block, $field));
    }

    public static function sharedBlockMissing(int $id): self
    {
        return new self(sprintf('Shared block [%d] does not exist.', $id));
    }
}
