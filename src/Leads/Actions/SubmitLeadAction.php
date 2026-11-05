<?php

namespace Zuber\Kit\Leads\Actions;

use Illuminate\Database\ConnectionInterface;
use Zuber\Kit\Leads\Data\LeadData;
use Zuber\Kit\Leads\Exceptions\TooManyLeadsException;
use Zuber\Kit\Leads\Models\Lead;
use Zuber\Kit\Leads\Services\LeadService;

/**
 * Посетитель оставляет заявку: она сохраняется, команде уходит письмо (после коммита, из очереди).
 */
final readonly class SubmitLeadAction
{
    public function __construct(
        private ConnectionInterface $db,
        private LeadService $leads,
    ) {}

    /**
     * @throws TooManyLeadsException
     */
    /**
     * @param string|null $mailTo свои адреса формы для письма
     */
    public function handle(LeadData $data, string $ip, ?string $mailTo = null): Lead
    {
        $lead = $this->db->transaction(fn (): Lead => $this->leads->submit($data, $ip));

        $this->leads->notifyTeam($lead, $mailTo);

        return $lead;
    }
}
