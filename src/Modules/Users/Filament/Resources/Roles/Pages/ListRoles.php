<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages;

use Filament\Resources\Pages\ListRecords;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\RoleResource;

final class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;
}
