<?php

namespace Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Modules\Pages\Actions\SaveSharedBlockAction;
use Zuber\Kit\Modules\Pages\Data\SharedBlockData;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\SharedBlockResource;

final class CreateSharedBlock extends CreateRecord
{
    protected static string $resource = SharedBlockResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveSharedBlockAction::class)->handle(SharedBlockData::fromForm($data));
    }
}
