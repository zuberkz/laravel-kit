<?php

namespace Zuber\Kit\Pages\Filament\Resources\Pages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Pages\Actions\SavePageAction;
use Zuber\Kit\Pages\Data\PageData;
use Zuber\Kit\Pages\Filament\Resources\Pages\PageResource;

final class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SavePageAction::class)->handle(PageData::fromForm($data));
    }
}
