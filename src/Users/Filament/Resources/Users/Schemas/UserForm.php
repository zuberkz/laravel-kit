<?php

namespace Zuber\Kit\Users\Filament\Resources\Users\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Zuber\Kit\Support\PasswordSupport;
use Zuber\Kit\Users\Data\UsersSettingsData;
use Zuber\Kit\Users\Filament\Resources\Users\UserResource;
use Zuber\Kit\Users\Services\UserAccessService;

/**
 * Форма пользователя: слева профиль и пароль, справа роли и сведения.
 */
final class UserForm
{
    /**
     * Событие браузера, по которому поле пароля показывает сгенерированный пароль открытым.
     */
    private const string PASSWORD_GENERATED_EVENT = 'kit-password-generated';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([self::profile(), self::password()])->columnSpan(['lg' => 2]),
                Group::make([self::roles(), self::details()])->columnSpan(['lg' => 1]),
            ]);
    }

    private static function profile(): Section
    {
        return Section::make(__('Профиль'))
            ->icon(Heroicon::OutlinedUserCircle)
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label(__('Имя'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    private static function password(): Section
    {
        return Section::make(__('Пароль'))
            ->icon(Heroicon::OutlinedKey)
            ->description(fn (string $operation): string => $operation === 'create'
                ? __('Сгенерируйте пароль, скопируйте и передайте пользователю: после сохранения его не увидит никто.')
                : __('Оставьте поле пустым, чтобы пароль не менялся.'))
            ->schema([
                TextInput::make('password')
                    ->hiddenLabel()
                    ->placeholder(fn (string $operation): string => $operation === 'create' ? __('Новый пароль') : __('Новый пароль, если нужно сменить'))
                    ->password()
                    ->revealable()
                    ->suffixAction(self::generatePasswordAction())
                    ->copyable(copyMessage: __('Пароль скопирован'))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->rule(Password::default())
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->autocomplete('new-password')
                    ->extraInputAttributes(['class' => 'font-mono tracking-wide'])
                    ->extraAlpineAttributes(['x-on:' . self::PASSWORD_GENERATED_EVENT . '.window' => 'setPasswordRevealed(true)']),
            ]);
    }

    private static function generatePasswordAction(): Action
    {
        return Action::make('generatePassword')
            ->label(__('Сгенерировать'))
            ->tooltip(__('Сгенерировать пароль'))
            ->icon(Heroicon::OutlinedSparkles)
            ->action(function (Set $set, Component $livewire): void {
                $set('password', PasswordSupport::generate());
                $livewire->dispatch(self::PASSWORD_GENERATED_EVENT);

                Notification::make()
                    ->success()
                    ->title(__('Пароль сгенерирован'))
                    ->body(__('Скопируйте его кнопкой рядом с полем и передайте пользователю.'))
                    ->send();
            });
    }

    private static function roles(): Section
    {
        $settings = app(UsersSettingsData::class);

        return Section::make(__('Роли'))
            ->icon(Heroicon::OutlinedShieldCheck)
            ->description(__('Роль определяет, что пользователь может делать в админке.'))
            ->schema([
                CheckboxList::make('roles')
                    ->hiddenLabel()
                    ->options(fn (): array => app(UserAccessService::class)->assignableRoles(UserResource::currentActor()))
                    ->descriptions($settings->roleDescriptions()),
            ]);
    }

    private static function details(): Section
    {
        return Section::make(__('Сведения'))
            ->icon(Heroicon::OutlinedInformationCircle)
            ->visible(fn (string $operation): bool => $operation === 'edit')
            ->schema([
                TextEntry::make('created_at')
                    ->label(__('Создан'))
                    ->dateTime('d.m.Y H:i'),

                TextEntry::make('email_verified_at')
                    ->label(__('Email подтверждён'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder(__('Не подтверждён')),
            ]);
    }
}
