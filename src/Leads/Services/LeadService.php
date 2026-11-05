<?php

namespace Zuber\Kit\Leads\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Notifications\AnonymousNotifiable;
use Zuber\Kit\Leads\Data\LeadData;
use Zuber\Kit\Leads\Enums\LeadStatusEnum;
use Zuber\Kit\Leads\Exceptions\TooManyLeadsException;
use Zuber\Kit\Leads\Models\Lead;
use Zuber\Kit\Leads\Notifications\NewLeadNotification;
use Zuber\Kit\Leads\Repositories\LeadRepository;

/**
 * Заявки с сайта: не чаще kit.leads.per_minute с одного IP, письмо — на kit.leads.mail_to
 * (список или строка через запятую).
 */
final readonly class LeadService
{
    public function __construct(
        private LeadRepository $leads,
        private RateLimiter $limiter,
        private Repository $config,
        private Dispatcher $notifications,
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
     * Письмо на адреса формы, а если их нет — на kit.leads.mail_to; нет адресов — заявка только в админке.
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

        if ($emails === []) {
            return;
        }

        $this->notifications->send((new AnonymousNotifiable)->route('mail', $emails), new NewLeadNotification($lead));
    }

    public function markProcessed(Lead $lead): Lead
    {
        return $this->leads->setStatus($lead, LeadStatusEnum::PROCESSED);
    }
}
