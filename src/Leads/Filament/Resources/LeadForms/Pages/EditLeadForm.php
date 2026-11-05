<?php

namespace Zuber\Kit\Leads\Filament\Resources\LeadForms\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Exceptions\DomainException;
use Zuber\Kit\Leads\Actions\SaveLeadFormAction;
use Zuber\Kit\Leads\Data\LeadFormData;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\LeadFormResource;
use Zuber\Kit\Leads\Models\LeadForm;

final class EditLeadForm extends EditRecord
{
    protected static string $resource = LeadFormResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var LeadForm $record */
        try {
            return app(SaveLeadFormAction::class)->handle(LeadFormData::fromForm($data), $record);
        } catch (DomainException $exception) {
            report($exception);
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw new Halt;
        }
    }
}
