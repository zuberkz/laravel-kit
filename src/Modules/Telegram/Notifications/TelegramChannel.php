<?php

namespace Zuber\Kit\Modules\Telegram\Notifications;

use Illuminate\Notifications\Notification;
use Zuber\Kit\Modules\Telegram\Clients\TelegramClient;
use Zuber\Kit\Modules\Telegram\Contracts\TelegramNotificationInterface;
use Zuber\Kit\Modules\Telegram\Exceptions\TelegramUnavailableException;

/**
 * Канал уведомлений Telegram: куда — маршрут получателя ROUTE (['chat_id' => …, 'thread_id' => …]:
 * AnonymousNotifiable или routeNotificationForTelegram() модели), что — toTelegram() уведомления.
 */
final readonly class TelegramChannel
{
    /** Имя маршрута получателя: (new AnonymousNotifiable)->route(TelegramChannel::ROUTE, [...]) */
    public const string ROUTE = 'telegram';

    public function __construct(
        private TelegramClient $telegram,
    ) {}

    /**
     * @throws TelegramUnavailableException
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $route = method_exists($notifiable, 'routeNotificationFor') ? $notifiable->routeNotificationFor(self::ROUTE, $notification) : null;
        $chatId = is_array($route) ? ($route['chat_id'] ?? null) : null;

        if (! $notification instanceof TelegramNotificationInterface || ! (is_int($chatId) || (is_string($chatId) && $chatId !== ''))) {
            return;
        }

        $threadId = $route['thread_id'] ?? null;

        $this->telegram->sendMessage($chatId, $notification->toTelegram($notifiable)->toHtml(), is_int($threadId) ? $threadId : null);
    }
}
