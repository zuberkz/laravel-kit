<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;

/**
 * Роль: название и права галочками. У роли из кода название не меняется — поле показывает её подпись.
 */
final class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([self::role(), self::permissions()]);
    }

    private static function role(): Section
    {
        $settings = app(UsersSettingsData::class);

        return Section::make(__('Роль'))
            ->icon(Heroicon::OutlinedShieldCheck)
            ->schema([
                TextInput::make('name')
                    ->label(__('Название'))
                    ->placeholder(__('Например, Контент-менеджер'))
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Model $record): bool => self::isCodeRole($record))
                    ->formatStateUsing(fn (?string $state, ?Model $record): ?string => self::isCodeRole($record) && $state !== null ? $settings->roleLabel($state) : $state)
                    ->helperText(fn (?Model $record): ?string => self::isCodeRole($record) ? self::codeRoleHint() : null),
            ]);
    }

    private static function permissions(): Section
    {
        $settings = app(UsersSettingsData::class);

        return Section::make(__('Права'))
            ->icon(Heroicon::OutlinedKey)
            ->description(__('Отметьте, что может пользователь с этой ролью. Список прав задаёт код сайта.'))
            ->schema([
                CheckboxList::make('permissions')
                    ->hiddenLabel()
                    ->options($settings->permissionOptions())
                    ->descriptions($settings->permissionDescriptions())
                    ->bulkToggleable()
                    ->columns(['md' => 2]),
            ]);
    }

    private static function codeRoleHint(): string
    {
        return __('Роль задана в коде сайта: название не меняется, удалить её нельзя. Права — как у любой роли.');
    }

    private static function isCodeRole(?Model $record): bool
    {
        return $record instanceof Role && app(UsersSettingsData::class)->isCodeRole($record->name);
    }
}
