<?php

namespace Zuber\Kit\Leads\Filament\Resources\Leads\Pages;

use Filament\Resources\Pages\ListRecords;
use Zuber\Kit\Leads\Filament\Resources\Leads\LeadResource;

final class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;
}
