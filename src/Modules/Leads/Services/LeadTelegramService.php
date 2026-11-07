<?php

namespace Zuber\Kit\Modules\Leads\Services;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Notifications\AnonymousNotifiable;
use Zuber\Kit\Modules\Leads\Clients\TelegramClient;
use Zuber\Kit\Modules\Leads\Data\TelegramChatData;
use Zuber\Kit\Modules\Leads\Exceptions\TelegramUnavailableException;
use Zuber\Kit\Modules\Leads\Filament\Resources\Leads\LeadResource;
use Zuber\Kit\Modules\Leads\Models\Lead;
use Zuber\Kit\Modules\Leads\Notifications\NewLeadNotification;
use Zuber\Kit\Modules\Leads\Notifications\TelegramChannel;

/**
 * Заявки в Telegram: включено, когда заданы бот и чат (kit.leads.telegram). Тема (thread_id) — чтобы
 * заявки шли в свою тему группы, а не в общий чат. Ссылка на заявки — в панели Filament с LeadsPlugin.
 */
final readonly class LeadTelegramService
{
    public function __construct(
        private TelegramClient $telegram,
        private Dispatcher $notifications,
        private UrlGenerator $url,
        #[Config('kit.leads.telegram.chat_id')]
        private int|string|null $chatId = null,
        #[Config('kit.leads.telegram.thread_id')]
        private int|string|null $threadId = null,
    ) {}

    public function enabled(): bool
    {
        return $this->telegram->isConfigured() && filled($this->chatId);
    }

    /**
     * Маршрут получателя для TelegramChannel; null — Telegram не настроен.
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
     * Список заявок в админке: панель Filament, где подключён LeadsPlugin; без неё — null.
     */
    public function adminUrl(): ?string
    {
        if (! class_exists(Filament::class)) {
            return null;
        }

        $panel = collect(Filament::getPanels())->first(fn (Panel $panel): bool => $panel->hasPlugin('kit-leads'));

        return $panel instanceof Panel ? LeadResource::getUrl(panel: $panel->getId()) : null;
    }

    /**
     * Пробная заявка сразу, без очереди: образец, в базу не сохраняется.
     *
     * @throws TelegramUnavailableException
     */
    public function sendTest(): void
    {
        $route = $this->route();

        if ($route === null) {
            return;
        }

        $sample = new Lead([
            'answers' => [
                ['label' => __('Имя'), 'value' => __('Проверка')],
                ['label' => __('Телефон'), 'value' => '+7 700 000-00-00'],
            ],
            'source' => __('Проверка уведомлений'),
            'url' => $this->url->to('/'),
        ]);

        $title = __('Тестовая заявка — так будут приходить заявки с :site', ['site' => parse_url($this->url->to('/'), PHP_URL_HOST)]);

        $this->notifications->sendNow((new AnonymousNotifiable)->route(TelegramChannel::ROUTE, $route), new NewLeadNotification($sample, $this->adminUrl(), $title));
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
     * Тема для заявок в группе kit.leads.telegram.chat_id; null — бот или чат не заданы.
     *
     * @throws TelegramUnavailableException
     */
    public function createTopic(string $name): ?int
    {
        return $this->enabled() && $this->chatId !== null ? $this->telegram->createForumTopic($this->chatId, $name) : null;
    }
}
