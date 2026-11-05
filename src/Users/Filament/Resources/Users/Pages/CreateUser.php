<?php

namespace Zuber\Kit\Users\Filament\Resources\Users\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Exceptions\DomainException;
use Zuber\Kit\Users\Actions\CreateUserAction;
use Zuber\Kit\Users\Data\UserData;
use Zuber\Kit\Users\Filament\Resources\Users\UserResource;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * Пользователя пишет action, а не страница; отказ показывается уведомлением, форма остаётся.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateUserAction::class)->handle(UserData::fromForm($data), UserResource::currentActor());
        } catch (DomainException $exception) {
            report($exception);
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw new Halt;
        }
    }

    protected function getRedirectUrl(): string
    {
        return UserResource::getUrl();
    }
}
