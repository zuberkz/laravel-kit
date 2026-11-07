<?php

namespace Zuber\Kit\Modules\Telegram\Data;

/**
 * Чат, где боту писали или куда его добавили, — для настройки Telegram (kit:telegram).
 */
final readonly class TelegramChatData
{
    /**
     * @param array<int, array{id: int, title: string}> $topics темы группы, в которых были сообщения
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public array $topics = [],
    ) {}
}
