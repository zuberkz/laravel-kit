<?php

namespace Zuber\Kit\Modules\Leads\Actions;

use Zuber\Kit\Modules\Leads\Models\Lead;
use Zuber\Kit\Modules\Leads\Services\LeadService;

/**
 * Менеджер отмечает, что по заявке связались.
 */
final readonly class MarkLeadProcessedAction
{
    public function __construct(private LeadService $leads) {}

    public function handle(Lead $lead): Lead
    {
        return $this->leads->markProcessed($lead);
    }
}
