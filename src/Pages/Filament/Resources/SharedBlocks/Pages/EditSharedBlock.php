<?php

namespace Zuber\Kit\Pages\Filament\Resources\SharedBlocks\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Pages\Actions\SaveSharedBlockAction;
use Zuber\Kit\Pages\Data\SharedBlockData;
use Zuber\Kit\Pages\Filament\Resources\SharedBlocks\SharedBlockResource;
use Zuber\Kit\Pages\Models\SharedBlock;

final class EditSharedBlock extends EditRecord
{
    protected static string $resource = SharedBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SharedBlock $record */
        return app(SaveSharedBlockAction::class)->handle(SharedBlockData::fromForm($data), $record);
    }
}
