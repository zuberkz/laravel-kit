<?php

namespace Zuber\Kit\Users\Filament\Resources\Users\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
use Zuber\Kit\Support\PasswordSupport;

/**
 * Генерирует пароль в поле и просит поле показать его открытым: поле слушает событие
 * PASSWORD_GENERATED_EVENT (UserForm).
 */
final class GeneratePasswordAction extends Action
{
    public const string PASSWORD_GENERATED_EVENT = 'kit-password-generated';

    public static function getDefaultName(): string
    {
        return 'generatePassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Сгенерировать'));
        $this->tooltip(__('Сгенерировать пароль'));
        $this->icon(Heroicon::OutlinedSparkles);

        $this->action(function (Set $set, Component $livewire): void {
            $set('password', PasswordSupport::generate());
            $livewire->dispatch(self::PASSWORD_GENERATED_EVENT);

            Notification::make()
                ->success()
                ->title(__('Пароль сгенерирован'))
                ->body(__('Скопируйте его кнопкой рядом с полем и передайте пользователю.'))
                ->send();
        });
    }
}
