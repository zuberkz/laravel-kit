<?php

namespace Zuber\Kit\Leads\Filament\Resources\LeadForms\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\LeadFormResource;

final class ListLeadForms extends ListRecords
{
    protected static string $resource = LeadFormResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
