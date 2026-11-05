<?php

namespace Zuber\Kit\Leads\Filament\Resources\LeadForms\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Exceptions\DomainException;
use Zuber\Kit\Leads\Actions\SaveLeadFormAction;
use Zuber\Kit\Leads\Data\LeadFormData;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\LeadFormResource;

final class CreateLeadForm extends CreateRecord
{
    protected static string $resource = LeadFormResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(SaveLeadFormAction::class)->handle(LeadFormData::fromForm($data));
        } catch (DomainException $exception) {
            report($exception);
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw new Halt;
        }
    }
}
