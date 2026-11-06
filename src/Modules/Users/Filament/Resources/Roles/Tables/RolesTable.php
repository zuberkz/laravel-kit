<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Roles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\RoleResource;

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
                    ->weight('medium')
                    ->wrap(),

                TextColumn::make('permissions.name')
                    ->label(__('Права'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => $settings->permissionLabel($state))
                    ->limitList(6)
                    ->expandableLimitedList()
                    ->wrap()
                    ->placeholder(__('Нет прав')),

                TextColumn::make('users_count')
                    ->label(__('Пользователей'))
                    ->numeric()
                    ->alignEnd(),
            ])
            ->defaultSort('id')
            ->paginated(false)
            ->recordActions([
                EditAction::make(),
                RoleResource::deleteAction(),
            ])
            ->emptyStateHeading(__('Ролей пока нет'))
            ->emptyStateDescription(__('Создайте роль и отметьте, что она может.'));
    }
}
