<?php

namespace Zuber\Kit\Modules\Telegram\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Telegram не принял запрос: нет сети, неверный токен, chat id или разметка. Сообщение — ответ Telegram.
 */
final class TelegramUnavailableException extends RuntimeException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self(__('Telegram недоступен: :reason', ['reason' => $reason]), 0, $previous);
    }
}
