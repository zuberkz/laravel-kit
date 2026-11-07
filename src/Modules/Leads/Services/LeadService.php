<?php

namespace Zuber\Kit\Modules\Leads\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Notifications\AnonymousNotifiable;
use Zuber\Kit\Modules\Leads\Data\LeadData;
use Zuber\Kit\Modules\Leads\Enums\LeadStatusEnum;
use Zuber\Kit\Modules\Leads\Exceptions\TooManyLeadsException;
use Zuber\Kit\Modules\Leads\Models\Lead;
use Zuber\Kit\Modules\Leads\Notifications\NewLeadNotification;
use Zuber\Kit\Modules\Leads\Notifications\TelegramChannel;
use Zuber\Kit\Modules\Leads\Repositories\LeadRepository;

/**
 * Заявки с сайта: не чаще kit.leads.per_minute с одного IP; команде — письмо на kit.leads.mail_to (список
 * или строка через запятую) и сообщение в Telegram (kit.leads.telegram), что из этого настроено.
 */
final readonly class LeadService
{
    public function __construct(
        private LeadRepository $leads,
        private RateLimiter $limiter,
        private Repository $config,
        private Dispatcher $notifications,
        private LeadTelegramService $telegram,
    ) {}

    /**
     * @throws TooManyLeadsException
     */
    public function submit(LeadData $data, string $ip): Lead
    {
        $key = 'kit-leads:' . $ip;
        $perMinute = $this->config->get('kit.leads.per_minute');

        if ($this->limiter->tooManyAttempts($key, is_int($perMinute) ? $perMinute : 5)) {
            throw TooManyLeadsException::make();
        }

        $this->limiter->hit($key);

        return $this->leads->create([
            'lead_form_id' => $data->formId,
            'name' => $data->name,
            'phone' => $data->phone,
            'email' => $data->email,
            'answers' => $data->answers,
            'source' => $data->source,
            'url' => $data->url,
            'status' => LeadStatusEnum::NEW,
        ]);
    }

    /**
     * Письмо на адреса формы, а если их нет — на kit.leads.mail_to, и сообщение в Telegram, если он настроен;
     * ни того ни другого — заявка только в админке.
     */
    public function notifyTeam(Lead $lead, ?string $formMailTo = null): void
    {
        $mailTo = $formMailTo ?? $this->config->get('kit.leads.mail_to');

        // Список или строка через запятую — так адреса задаются одной переменной .env
        $emails = collect(is_string($mailTo) ? explode(',', $mailTo) : (array) $mailTo)
            ->map(fn (mixed $email): mixed => is_string($email) ? trim($email) : null)
            ->filter(fn (mixed $email): bool => is_string($email) && $email !== '')
            ->values()
            ->all();

        $team = new AnonymousNotifiable;

        if ($emails !== []) {
            $team->route('mail', $emails);
        }

        $telegram = $this->telegram->route();

        if ($telegram !== null) {
            $team->route(TelegramChannel::ROUTE, $telegram);
        }

        if ($team->routes === []) {
            return;
        }

        $this->notifications->send($team, new NewLeadNotification($lead, $telegram === null ? null : $this->telegram->adminUrl()));
    }

    public function markProcessed(Lead $lead): Lead
    {
        return $this->leads->setStatus($lead, LeadStatusEnum::PROCESSED);
    }
}
