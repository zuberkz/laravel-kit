<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Core\Exceptions\DomainException;
use Zuber\Kit\Modules\Users\Actions\CreateRoleAction;
use Zuber\Kit\Modules\Users\Data\RoleData;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\RoleResource;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\UserResource;

final class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateRoleAction::class)->handle(RoleData::fromForm($data), UserResource::currentActor());
        } catch (DomainException $exception) {
            report($exception);
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw new Halt;
        }
    }

    protected function getRedirectUrl(): string
    {
        return RoleResource::getUrl();
    }
}
