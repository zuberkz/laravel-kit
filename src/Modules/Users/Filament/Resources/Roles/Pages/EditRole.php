<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;
use Zuber\Kit\Core\Exceptions\DomainException;
use Zuber\Kit\Modules\Users\Actions\UpdateRoleAction;
use Zuber\Kit\Modules\Users\Data\RoleData;
use Zuber\Kit\Modules\Users\Exceptions\UsersPluginException;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\RoleResource;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\UserResource;
use Zuber\Kit\Modules\Users\Repositories\RoleRepository;

final class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RoleResource::deleteAction(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $role = $this->getRecord();

        return [...$data, 'permissions' => $role instanceof Role ? app(RoleRepository::class)->permissionNames($role) : []];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Role) {
            throw UsersPluginException::roleModel($record::class);
        }

        try {
            app(UpdateRoleAction::class)->handle($record, RoleData::fromForm($data, $record->name), UserResource::currentActor());
        } catch (DomainException $exception) {
            report($exception);
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw new Halt;
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return RoleResource::getUrl();
    }
}
