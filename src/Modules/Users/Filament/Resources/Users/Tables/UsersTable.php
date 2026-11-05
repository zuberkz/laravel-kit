<?php

namespace Zuber\Kit\Modules\Users\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Zuber\Kit\Modules\Users\Data\UsersSettingsData;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\UserResource;
use Zuber\Kit\Modules\Users\Repositories\UserRepository;
use Zuber\Kit\Modules\Users\Services\UserAccessService;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        $settings = app(UsersSettingsData::class);

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Пользователь'))
                    ->description(fn (Model $record): ?string => is_string($record->getAttribute('email')) ? $record->getAttribute('email') : null)
                    ->searchable(['name', 'email'])
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(__('Роли'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $settings->roleLabel($state))
                    ->color(fn (string $state): string => $state === $settings->superAdminRole ? 'warning' : 'primary')
                    ->placeholder(__('Без ролей')),

                IconColumn::make('email_verified')
                    ->label(__('Email подтверждён'))
                    ->state(fn (Model $record): bool => $record->getAttribute('email_verified_at') !== null)
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('Создан'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('roles')
                    ->label(__('Роль'))
                    ->multiple()
                    ->options(fn (): array => app(UserAccessService::class)->assignableRoles(UserResource::currentActor()))
                    ->query(fn (Builder $query, array $data): Builder => app(UserRepository::class)->withAnyRole(
                        $query,
                        collect(Arr::wrap($data['values'] ?? []))->filter(fn (mixed $role): bool => is_string($role))->values()->all(),
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
