<?php

namespace Zuber\Kit\Leads\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Zuber\Kit\Leads\Models\Lead;
use Zuber\Kit\Support\PhoneSupport;

/**
 * Письмо команде о новой заявке: из очереди и только после сохранения заявки.
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
        $mail = (new MailMessage)
            ->subject(__('Новая заявка с сайта: :name', ['name' => $this->lead->name]))
            ->line(__('Имя: :name', ['name' => $this->lead->name]))
            ->line(__('Телефон: :phone', ['phone' => PhoneSupport::format($this->lead->phone)]));

        if ($this->lead->message !== null) {
            $mail->line(__('Комментарий: :message', ['message' => $this->lead->message]));
        }

        if ($this->lead->source !== null) {
            $mail->line(__('Форма: :source', ['source' => $this->lead->source]));
        }

        return $this->lead->url === null ? $mail : $mail->action(__('Страница заявки'), $this->lead->url);
    }
}
