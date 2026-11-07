<?php

namespace Zuber\Kit\Modules\Telegram\Contracts;

use Zuber\Kit\Modules\Telegram\Data\TelegramMessageData;

/**
 * Уведомление, которое умеет уйти в Telegram: канал TelegramChannel берёт сообщение из toTelegram().
 */
interface TelegramNotificationInterface
{
    public function toTelegram(object $notifiable): TelegramMessageData;
}
