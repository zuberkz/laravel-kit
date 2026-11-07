<?php

namespace Zuber\Kit\Modules\Leads\Commands;

use Illuminate\Console\Command;
use Zuber\Kit\Modules\Leads\Exceptions\TelegramUnavailableException;
use Zuber\Kit\Modules\Leads\Services\LeadTelegramService;

/**
 * Настройка заявок в Telegram: показывает chat id тех, кто писал боту, групп, куда его добавили, и id тем;
 * --topic создаёт тему для заявок в группе kit.leads.telegram.chat_id, --test шлёт пробную заявку.
 */
final class TelegramCommand extends Command
{
    protected $signature = 'kit:telegram
        {--topic= : Создать тему с этим названием в группе для заявок}
        {--test : Отправить пробную заявку в Telegram}';

    protected $description = 'Настроить заявки в Telegram: chat id, тема группы, пробное сообщение';

    public function handle(LeadTelegramService $telegram): int
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

    private function listChats(LeadTelegramService $telegram): int
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

        $this->components->info('Впишите chat id группы в kit.leads.telegram.chat_id (обычно TELEGRAM_CHAT_ID в .env), id темы — в thread_id, затем: php artisan kit:telegram --test');

        return self::SUCCESS;
    }

    private function createTopic(LeadTelegramService $telegram, string $name): int
    {
        $threadId = $telegram->createTopic($name);

        if ($threadId === null) {
            $this->components->error('Сначала задайте токен бота и chat id группы (kit.leads.telegram) — узнать chat id: php artisan kit:telegram');

            return self::FAILURE;
        }

        $this->components->info("Тема «{$name}» создана, её id — {$threadId}: впишите его в kit.leads.telegram.thread_id (TELEGRAM_THREAD_ID={$threadId})");

        return self::SUCCESS;
    }

    private function test(LeadTelegramService $telegram): int
    {
        if (! $telegram->enabled()) {
            $this->components->error('Задайте токен бота и chat id группы (kit.leads.telegram: TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID) — узнать chat id: php artisan kit:telegram');

            return self::FAILURE;
        }

        $telegram->sendTest();

        $this->components->info('Пробная заявка отправлена — проверьте Telegram.');

        return self::SUCCESS;
    }
}
