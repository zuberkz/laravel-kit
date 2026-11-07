<?php

namespace Zuber\Kit\Modules\Leads\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Zuber\Kit\Modules\Leads\Models\Lead;
use Zuber\Kit\Modules\Telegram\Contracts\TelegramNotificationInterface;
use Zuber\Kit\Modules\Telegram\Data\TelegramMessageData;
use Zuber\Kit\Modules\Telegram\Notifications\TelegramChannel;

/**
 * Новая заявка — команде со всеми ответами: письмом и/или в Telegram, смотря какие маршруты у получателя
 * (mail, TelegramChannel::ROUTE). Из очереди и только после сохранения заявки; каждый канал — своя задача
 * очереди, поэтому сбой Telegram не задерживает письмо, и наоборот.
 */
final class NewLeadNotification extends Notification implements ShouldQueue, TelegramNotificationInterface
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    /**
     * @param string|null $adminUrl список заявок в админке (ссылка в Telegram); null — без ссылки
     */
    public function __construct(
        public readonly Lead $lead,
        public readonly ?string $adminUrl = null,
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
     * Ответы формы (а без них — контакты из заявки), страница и ссылка на заявки в админке.
     */
    public function toTelegram(object $notifiable): TelegramMessageData
    {
        $lead = $this->lead;

        $fields = collect($lead->answers)->map(fn (array $answer): array => ['label' => $answer['label'], 'value' => $answer['value']]);

        if ($fields->isEmpty()) {
            $fields = collect([[__('Имя'), $lead->name], [__('Телефон'), $lead->phone], [__('Почта'), $lead->email]])
                ->filter(fn (array $field): bool => filled($field[1]))
                ->map(fn (array $field): array => ['label' => (string) $field[0], 'value' => (string) $field[1]]);
        }

        if (filled($lead->url)) {
            $fields->push(['label' => __('Страница'), 'value' => $lead->url]);
        }

        return new TelegramMessageData(
            title: __('Заявка с сайта'),
            fields: $fields->values()->all(),
            subtitle: $lead->source,
            link: $this->adminUrl,
            linkLabel: __('Открыть в админке'),
        );
    }
}
