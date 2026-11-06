<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\RoleResource;

final class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
