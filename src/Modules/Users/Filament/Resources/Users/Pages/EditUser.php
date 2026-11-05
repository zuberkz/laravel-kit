<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Core\Exceptions\DomainException;
use Zuber\Kit\Modules\Users\Actions\UpdateUserAction;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Data\UserData;
use Zuber\Kit\Modules\Users\Exceptions\UsersPluginException;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\UserResource;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Роли — в поле формы; пароль в форму не попадает никогда, даже хэшем.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getRecord();
        unset($data['password']);

        return [...$data, 'roles' => $user instanceof HasRolesInterface ? $user->getRoleNames()->all() : []];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof HasRolesInterface) {
            throw UsersPluginException::userModelWithoutRoles($record::class);
        }

        try {
            return app(UpdateUserAction::class)->handle($record, UserData::fromForm($data), UserResource::currentActor());
        } catch (DomainException $exception) {
            report($exception);
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw new Halt;
        }
    }
}
