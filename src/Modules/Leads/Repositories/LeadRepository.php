<?php

namespace Zuber\Kit\Modules\Leads\Repositories;

use Zuber\Kit\Modules\Leads\Enums\LeadStatusEnum;
use Zuber\Kit\Modules\Leads\Models\Lead;

final readonly class LeadRepository
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Lead
    {
        return Lead::query()->create($attributes);
    }

    public function setStatus(Lead $lead, LeadStatusEnum $status): Lead
    {
        $lead->update(['status' => $status]);

        return $lead;
    }

    public function countNew(): int
    {
        return Lead::query()->where('status', LeadStatusEnum::NEW)->count();
    }
}
