<?php

namespace Zuber\Kit\Modules\Telegram\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Zuber\Kit\Modules\Telegram\Clients\TelegramClient;
use Zuber\Kit\Modules\Telegram\Contracts\TelegramNotificationInterface;
use Zuber\Kit\Modules\Telegram\Data\TelegramChatData;
use Zuber\Kit\Modules\Telegram\Exceptions\TelegramUnavailableException;
use Zuber\Kit\Modules\Telegram\Notifications\TelegramChannel;
use Zuber\Kit\Modules\Telegram\Notifications\TelegramTestNotification;

/**
 * Telegram команды сайта: включён, когда заданы бот и чат (kit.telegram). Тема (thread_id) — чтобы уведомления
 * сайта шли в свою тему группы, а не в общий чат. Любое уведомление с toTelegram() — notify(), заявки кита
 * берут route() и уходят вместе с письмом.
 */
final readonly class TelegramService
{
    public function __construct(
        private TelegramClient $telegram,
        private Dispatcher $notifications,
        private UrlGenerator $url,
        #[Config('kit.telegram.chat_id')]
        private int|string|null $chatId = null,
        #[Config('kit.telegram.thread_id')]
        private int|string|null $threadId = null,
    ) {}

    public function enabled(): bool
    {
        return $this->telegram->isConfigured() && filled($this->chatId);
    }

    /**
     * Маршрут чата команды для TelegramChannel; null — Telegram не настроен.
     *
     * @return array{chat_id: int|string, thread_id: int|null}|null
     */
    public function route(): ?array
    {
        if (! $this->enabled() || $this->chatId === null) {
            return null;
        }

        return ['chat_id' => $this->chatId, 'thread_id' => is_numeric($this->threadId) ? (int) $this->threadId : null];
    }

    /**
     * Уведомление в чат команды; через очередь, если оно ShouldQueue. Telegram не настроен — ничего.
     */
    public function notify(Notification&TelegramNotificationInterface $notification): void
    {
        $route = $this->route();

        if ($route !== null) {
            $this->notifications->send((new AnonymousNotifiable)->route(TelegramChannel::ROUTE, $route), $notification);
        }
    }

    /**
     * Пробное сообщение сразу, без очереди. Telegram не настроен — ничего.
     *
     * @throws TelegramUnavailableException
     */
    public function sendTest(): void
    {
        $route = $this->route();

        if ($route !== null) {
            $this->notifications->sendNow((new AnonymousNotifiable)->route(TelegramChannel::ROUTE, $route), new TelegramTestNotification($this->url->to('/')));
        }
    }

    /**
     * @throws TelegramUnavailableException
     *
     * @return list<TelegramChatData>
     */
    public function chats(): array
    {
        return $this->telegram->chats();
    }

    /**
     * Тема в группе kit.telegram.chat_id; null — бот или чат не заданы.
     *
     * @throws TelegramUnavailableException
     */
    public function createTopic(string $name): ?int
    {
        return $this->enabled() && $this->chatId !== null ? $this->telegram->createForumTopic($this->chatId, $name) : null;
    }
}
