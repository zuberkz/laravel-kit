<?php

namespace Zuber\Kit\Modules\Telegram\Notifications;

use Illuminate\Notifications\Notification;
use Zuber\Kit\Modules\Telegram\Contracts\TelegramNotificationInterface;
use Zuber\Kit\Modules\Telegram\Data\TelegramMessageData;

/**
 * Пробное сообщение после настройки (kit:telegram --test): уходит сразу, без очереди.
 */
final class TelegramTestNotification extends Notification implements TelegramNotificationInterface
{
    public function __construct(
        public readonly string $siteUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram(object $notifiable): TelegramMessageData
    {
        $host = parse_url($this->siteUrl, PHP_URL_HOST);

        return new TelegramMessageData(
            title: __('Тестовое сообщение — так будут приходить уведомления с :site', ['site' => is_string($host) ? $host : $this->siteUrl]),
            fields: [['label' => __('Сайт'), 'value' => $this->siteUrl]],
            icon: '🧪',
        );
    }
}
