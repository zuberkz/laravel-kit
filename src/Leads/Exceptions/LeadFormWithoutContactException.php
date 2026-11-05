<?php

namespace Zuber\Kit\Leads\Exceptions;

use Zuber\Kit\Exceptions\DomainException;

/**
 * В форме нет ни телефона, ни email — с автором заявки не связаться.
 */
final class LeadFormWithoutContactException extends DomainException
{
    public static function make(): self
    {
        return new self(__('Добавьте в форму поле «Телефон» или «Email»: иначе с клиентом не связаться.'));
    }
}
