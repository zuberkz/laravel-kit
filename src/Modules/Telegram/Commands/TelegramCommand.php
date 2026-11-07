<?php

namespace Zuber\Kit\Modules\Telegram\Commands;

use Illuminate\Console\Command;
use Zuber\Kit\Modules\Telegram\Exceptions\TelegramUnavailableException;
use Zuber\Kit\Modules\Telegram\Services\TelegramService;

/**
 * Настройка Telegram: показывает chat id тех, кто писал боту, групп, куда его добавили, и id тем;
 * --topic создаёт тему для уведомлений сайта в группе kit.telegram.chat_id, --test шлёт пробное сообщение.
 */
final class TelegramCommand extends Command
{
    protected $signature = 'kit:telegram
        {--topic= : Создать тему с этим названием в группе для уведомлений сайта}
        {--test : Отправить пробное сообщение в Telegram}';

    protected $description = 'Настроить уведомления в Telegram: chat id, тема группы, пробное сообщение';

    public function handle(TelegramService $telegram): int
    {
        $topic = $this->option('topic');

        try {
            return match (true) {
                is_string($topic) && $topic !== '' => $this->createTopic($telegram, $topic),
                (bool) $this->option('test') => $this->test($telegram),
                default => $this->listChats($telegram),
            };
        } catch (TelegramUnavailableException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function listChats(TelegramService $telegram): int
    {
        $chats = $telegram->chats();

        if ($chats === []) {
            $this->components->warn('Боту ещё никто не писал. Добавьте его в группу и напишите там любое сообщение (или нажмите «Старт» в боте) и запустите команду снова.');

            return self::FAILURE;
        }

        foreach ($chats as $chat) {
            $this->components->twoColumnDetail("{$chat->title} <fg=gray>({$chat->type})</>", (string) $chat->id);

            foreach ($chat->topics as $topic) {
                $this->components->twoColumnDetail("  <fg=gray>тема</> {$topic['title']}", "thread {$topic['id']}");
            }
        }

        $this->components->info('Впишите chat id группы в kit.telegram.chat_id (обычно TELEGRAM_CHAT_ID в .env), id темы — в thread_id, затем: php artisan kit:telegram --test');

        return self::SUCCESS;
    }

    private function createTopic(TelegramService $telegram, string $name): int
    {
        $threadId = $telegram->createTopic($name);

        if ($threadId === null) {
            $this->components->error('Сначала задайте токен бота и chat id группы (kit.telegram) — узнать chat id: php artisan kit:telegram');

            return self::FAILURE;
        }

        $this->components->info("Тема «{$name}» создана, её id — {$threadId}: впишите его в kit.telegram.thread_id (TELEGRAM_THREAD_ID={$threadId})");

        return self::SUCCESS;
    }

    private function test(TelegramService $telegram): int
    {
        if (! $telegram->enabled()) {
            $this->components->error('Задайте токен бота и chat id группы (kit.telegram: TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID) — узнать chat id: php artisan kit:telegram');

            return self::FAILURE;
        }

        $telegram->sendTest();

        $this->components->info('Пробное сообщение отправлено — проверьте Telegram.');

        return self::SUCCESS;
    }
}
