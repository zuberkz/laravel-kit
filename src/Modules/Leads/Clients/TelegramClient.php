<?php

namespace Zuber\Kit\Modules\Leads\Clients;

use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Zuber\Kit\Modules\Leads\Data\TelegramChatData;
use Zuber\Kit\Modules\Leads\Exceptions\TelegramUnavailableException;

/**
 * Клиент Telegram Bot API — только транспорт: что отправить и куда, решают сервисы.
 * Токен бота — kit.leads.telegram.bot_token.
 */
final readonly class TelegramClient
{
    public function __construct(
        #[Config('kit.leads.telegram.bot_token')]
        private ?string $token = null,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->token);
    }

    /**
     * Сообщение в HTML-разметке Telegram (<b>, <i>, <a>), ссылки без превью.
     *
     * @param int|null $threadId тема группы с «Темами» (message_thread_id); null — общий чат
     *
     * @throws TelegramUnavailableException
     */
    public function sendMessage(int|string $chatId, string $html, ?int $threadId = null): void
    {
        $this->call('post', '/sendMessage', array_filter([
            'chat_id' => $chatId,
            'message_thread_id' => $threadId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * Новая тема в группе с «Темами» (бот — администратор с правом управлять темами).
     *
     *
     * @throws TelegramUnavailableException
     *
     * @return int id темы (message_thread_id)
     */
    public function createForumTopic(int|string $chatId, string $name): int
    {
        return self::int($this->call('post', '/createForumTopic', ['chat_id' => $chatId, 'name' => $name])->json('result.message_thread_id'));
    }

    /**
     * Чаты, где боту писали или куда его добавили, и темы групп, в которых были сообщения.
     *
     *
     * @throws TelegramUnavailableException
     *
     * @return list<TelegramChatData>
     */
    public function chats(): array
    {
        $updates = collect((array) $this->call('get', '/getUpdates')->json('result', []))->filter(fn (mixed $update): bool => is_array($update));

        $topics = $updates
            ->map(fn (array $update): mixed => $update['message'] ?? null)
            ->filter(fn (mixed $message): bool => is_array($message) && isset($message['message_thread_id']) && (($message['is_topic_message'] ?? false) || isset($message['forum_topic_created'])))
            ->map(fn (array $message): array => [
                'chat' => self::int(data_get($message, 'chat.id')),
                'id' => self::int($message['message_thread_id']),
                'title' => self::string(data_get($message, 'forum_topic_created.name') ?? data_get($message, 'reply_to_message.forum_topic_created.name') ?? '#' . self::int($message['message_thread_id'])),
            ])
            ->unique(fn (array $topic): string => $topic['chat'] . ':' . $topic['id']);

        $chats = $updates
            ->map(fn (array $update): mixed => data_get($update, 'message.chat') ?? data_get($update, 'my_chat_member.chat'))
            ->filter(fn (mixed $chat): bool => is_array($chat) && isset($chat['id']))
            ->unique('id')
            ->map(fn (array $chat): TelegramChatData => new TelegramChatData(
                id: self::int($chat['id']),
                type: self::string($chat['type'] ?? ''),
                title: self::string($chat['title'] ?? trim(self::string($chat['first_name'] ?? '') . ' ' . self::string($chat['last_name'] ?? ''))),
                topics: $topics
                    ->filter(fn (array $topic): bool => $topic['chat'] === self::int($chat['id']))
                    ->map(fn (array $topic): array => ['id' => $topic['id'], 'title' => $topic['title']])
                    ->values()
                    ->all(),
            ))
            ->all();

        return array_values($chats);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws TelegramUnavailableException
     */
    private function call(string $method, string $path, array $payload = []): Response
    {
        try {
            return $this->request()->{$method}($path, $payload)->throw();
        } catch (RequestException $e) {
            throw TelegramUnavailableException::because(self::string($e->response->json('description')) ?: (string) $e->response->status(), $e);
        } catch (ConnectionException $e) {
            throw TelegramUnavailableException::because($e->getMessage(), $e);
        }
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl('https://api.telegram.org/bot' . $this->token)
            ->acceptJson()
            ->timeout(15);
    }

    private static function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
