<?php

namespace Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\SharedBlockResource;

final class ListSharedBlocks extends ListRecords
{
    protected static string $resource = SharedBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
