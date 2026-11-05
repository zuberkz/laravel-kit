<?php

namespace Zuber\Kit\Users\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Zuber\Kit\Users\Data\UsersSettingsData;
use Zuber\Kit\Users\Filament\Resources\Users\Actions\GeneratePasswordAction;
use Zuber\Kit\Users\Filament\Resources\Users\UserResource;
use Zuber\Kit\Users\Services\UserAccessService;

/**
 * Форма пользователя: профиль, пароль и роли — одной колонкой, чтобы кнопки сохранения шли сразу
 * за ними; справа узкий блок «Сведения», только при редактировании.
 */
final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([self::profile(), self::password(), self::roles()])
                    ->columnSpan(fn (string $operation): array|string => $operation === 'edit' ? ['lg' => 2] : 'full'),
                Group::make([self::details()])
                    ->columnSpan(['lg' => 1])
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
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
                    ->suffixAction(GeneratePasswordAction::make())
                    // Буфер обмена браузер даёт только на https и localhost: локально — https://<проект>.local
                    ->copyable(copyMessage: __('Пароль скопирован'))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->rule(Password::default())
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->autocomplete('new-password')
                    ->extraInputAttributes(['class' => 'font-mono tracking-wide'])
                    ->extraAlpineAttributes(['x-on:' . GeneratePasswordAction::PASSWORD_GENERATED_EVENT . '.window' => 'setPasswordRevealed(true)']),
            ]);
    }

    /**
     * Ролей в проекте бывает много — выпадающий список с поиском; описания выбранных ролей — под полем.
     */
    private static function roles(): Section
    {
        return Section::make(__('Роли'))
            ->icon(Heroicon::OutlinedShieldCheck)
            ->description(__('Роль определяет, что пользователь может делать в админке.'))
            ->schema([
                Select::make('roles')
                    ->label(__('Роли'))
                    ->hiddenLabel()
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->placeholder(__('Выберите роли'))
                    ->options(fn (): array => app(UserAccessService::class)->assignableRoles(UserResource::currentActor()))
                    ->live()
                    ->helperText(fn (?array $state): ?HtmlString => self::roleDescriptions($state ?? [])),
            ]);
    }

    /**
     * «Роль — описание» по строке на каждую выбранную роль, у которой есть описание.
     *
     * @param array<mixed> $roles
     */
    private static function roleDescriptions(array $roles): ?HtmlString
    {
        $settings = app(UsersSettingsData::class);

        $lines = collect($roles)
            ->filter(fn (mixed $role): bool => is_string($role) && $settings->roleDescription($role) !== null)
            ->map(fn (string $role): string => '<b>' . e($settings->roleLabel($role)) . '</b> — ' . e($settings->roleDescription($role)));

        return $lines->isEmpty() ? null : new HtmlString($lines->implode('<br>'));
    }

    private static function details(): Section
    {
        return Section::make(__('Сведения'))
            ->icon(Heroicon::OutlinedInformationCircle)
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
