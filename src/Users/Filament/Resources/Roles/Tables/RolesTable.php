<?php

namespace Zuber\Kit\Users\Filament\Resources\Roles\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zuber\Kit\Users\Data\UsersSettingsData;

final class RolesTable
{
    public static function configure(Table $table): Table
    {
        $settings = app(UsersSettingsData::class);

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Роль'))
                    ->formatStateUsing(fn (string $state): string => $settings->roleLabel($state))
                    ->description(fn (string $state): ?string => $settings->roleDescription($state))
                    ->weight('medium'),

                TextColumn::make('permissions.name')
                    ->label(__('Права'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => $settings->permissionLabel($state))
                    ->limitList(6)
                    ->expandableLimitedList()
                    ->placeholder(__('Нет прав')),

                TextColumn::make('users_count')
                    ->label(__('Пользователей'))
                    ->numeric()
                    ->alignEnd(),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('Ролей пока нет'))
            ->emptyStateDescription(__('Роли создаёт сидер из RoleEnum проекта.'));
    }
}
