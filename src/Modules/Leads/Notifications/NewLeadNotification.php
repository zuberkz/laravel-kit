<?php

namespace Zuber\Kit\Modules\Leads\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Zuber\Kit\Modules\Leads\Models\Lead;

/**
 * Письмо команде о новой заявке со всеми ответами: из очереди и только после сохранения заявки.
 */
final class NewLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Lead $lead)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
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
}
