<?php

namespace Zuber\Kit\Exceptions;

use RuntimeException;

/**
 * Отказ по бизнес-правилу («заказ уже оплачен», «время занято») — нормальный исход, а не сбой:
 * сообщение — для пользователя (через __()), в лог как ошибка не пишется. Обработка — ExceptionsSetup.
 *
 *     throw OrderAlreadyPaidException::for($order);
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Данные для лога и отладки (id записи, пользователь) — не в тексте сообщения.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }
}
