<?php

namespace Zuber\Kit\Modules\Telegram\Data;

use Illuminate\Support\Str;

/**
 * Сообщение в Telegram: заголовок, подпись, поля «название: значение», свободный текст и ссылка (на запись
 * в админке). toHtml() собирает HTML-разметку Telegram: всё экранируется, длинное обрезается целыми строками
 * в пределах 4096 символов — ссылка остаётся всегда.
 */
final readonly class TelegramMessageData
{
    /** Предел Telegram — 4096 символов, с запасом */
    private const int LIMIT = 4000;
    private const int VALUE_LIMIT = 300;
    private const int TEXT_LIMIT = 1500;

    /**
     * @param array<int, array{label: string, value: string}> $fields
     * @param string|null                                     $text   свободный текст (сообщение посетителя) после полей
     */
    public function __construct(
        public string $title,
        public array $fields = [],
        public ?string $subtitle = null,
        public ?string $text = null,
        public ?string $link = null,
        public ?string $linkLabel = null,
        public string $icon = '🔔',
    ) {}

    public function toHtml(): string
    {
        $link = $this->link === null ? '' : "\n" . '<a href="' . e($this->link) . '">' . e($this->linkLabel ?? $this->link) . '</a>';
        $html = '';

        foreach ($this->lines() as $line) {
            if (mb_strlen($html . $line . "\n" . $link) > self::LIMIT) {
                $html .= "…\n";

                break;
            }

            $html .= $line . "\n";
        }

        return rtrim($html . $link);
    }

    /**
     * @return array<int, string>
     */
    private function lines(): array
    {
        $lines = [trim($this->icon . ' <b>' . e($this->title) . '</b>')];

        if (filled($this->subtitle)) {
            $lines[] = '<i>' . e($this->subtitle) . '</i>';
        }

        $lines[] = '';

        foreach ($this->fields as $field) {
            $lines[] = '<b>' . e($field['label']) . ':</b> ' . e(Str::limit($field['value'], self::VALUE_LIMIT));
        }

        if (filled($this->text)) {
            array_push($lines, '', e(Str::limit($this->text, self::TEXT_LIMIT)));
        }

        return $lines;
    }
}
