<?php

namespace Zuber\Kit\Modules\Leads\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Zuber\Kit\Modules\Leads\Models\Lead;

/**
 * Новая заявка — команде со всеми ответами: письмом и/или в Telegram, смотря какие маршруты у получателя
 * (mail, TelegramChannel::ROUTE). Из очереди и только после сохранения заявки; каждый канал — своя задача
 * очереди, поэтому сбой Telegram не задерживает письмо, и наоборот.
 */
final class NewLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Предел Telegram — 4096 символов, с запасом */
    private const int TELEGRAM_LIMIT = 4000;
    private const int VALUE_LIMIT = 300;

    public int $tries = 5;

    public int $timeout = 30;

    /**
     * @param string|null $adminUrl список заявок в админке (ссылка в Telegram); null — без ссылки
     * @param string|null $title    свой заголовок сообщения (пробная заявка); null — «Заявка с сайта»
     */
    public function __construct(
        public readonly Lead $lead,
        public readonly ?string $adminUrl = null,
        public readonly ?string $title = null,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return collect(array_keys($notifiable->routes))
            ->map(fn (int|string $route): ?string => match ($route) {
                'mail' => 'mail',
                TelegramChannel::ROUTE => TelegramChannel::class,
                default => null,
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject(__('Новая заявка с сайта: :name', ['name' => $this->lead->name ?? $this->lead->phone ?? $this->lead->email ?? '—']));

        if ($this->lead->source !== null) {
            $mail->line(__('Форма: :source', ['source' => $this->lead->source]));
        }

        foreach ($this->lead->answers as $answer) {
            $mail->line($answer['label'] . ': ' . $answer['value']);
        }

        return $this->lead->url === null ? $mail : $mail->action(__('Страница заявки'), $this->lead->url);
    }

    /**
     * Сообщение в HTML-разметке Telegram: всё, что ввёл посетитель, экранируется, длинное обрезается
     * целыми строками — ссылка на админку остаётся всегда.
     */
    public function toTelegram(): string
    {
        $link = $this->adminUrl === null ? '' : "\n" . '<a href="' . e($this->adminUrl) . '">' . e(__('Открыть в админке')) . '</a>';
        $text = '';

        foreach ($this->telegramLines() as $line) {
            if (mb_strlen($text . $line . "\n" . $link) > self::TELEGRAM_LIMIT) {
                $text .= "…\n";

                break;
            }

            $text .= $line . "\n";
        }

        return rtrim($text . $link);
    }

    /**
     * @return array<int, string>
     */
    private function telegramLines(): array
    {
        $lead = $this->lead;
        $lines = ['🔔 <b>' . e($this->title ?? __('Заявка с сайта')) . '</b>'];

        if (filled($lead->source)) {
            $lines[] = '<i>' . e($lead->source) . '</i>';
        }

        $lines[] = '';

        // В ответах формы уже есть имя и телефон; без ответов — контакты из заявки
        $answers = collect($lead->answers)->map(fn (array $answer): array => [$answer['label'], $answer['value']]);

        if ($answers->isEmpty()) {
            $answers = collect([[__('Имя'), $lead->name], [__('Телефон'), $lead->phone], [__('Почта'), $lead->email]])
                ->filter(fn (array $field): bool => filled($field[1]));
        }

        foreach ($answers as [$label, $value]) {
            $lines[] = '<b>' . e((string) $label) . ':</b> ' . e(Str::limit((string) $value, self::VALUE_LIMIT));
        }

        if (filled($lead->url)) {
            array_push($lines, '', '<b>' . e(__('Страница')) . ':</b> ' . e($lead->url));
        }

        return $lines;
    }
}
