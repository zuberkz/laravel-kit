<?php

namespace Zuber\Kit\Leads\Exceptions;

use Zuber\Kit\Exceptions\DomainException;

/**
 * С одного адреса слишком много заявок подряд — похоже на бота или на двойное нажатие.
 */
final class TooManyLeadsException extends DomainException
{
    public static function make(): self
    {
        return new self(__('Заявка уже отправлена. Если нужно что-то добавить, попробуйте через минуту.'));
    }
}
