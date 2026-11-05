<?php

namespace Zuber\Kit\Modules\Users\Repositories;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Modules\Users\Contracts\HasRolesInterface;
use Zuber\Kit\Modules\Users\Exceptions\UsersPluginException;

/**
 * Таблица пользователей проекта: модель берётся из auth.providers.users.model.
 */
final readonly class UserRepository
{
    public function __construct(private Repository $config) {}

    /**
     * @return class-string<Model&HasRolesInterface>
     */
    public function modelClass(): string
    {
        $model = $this->config->get('auth.providers.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class) || ! is_subclass_of($model, HasRolesInterface::class)) {
            throw UsersPluginException::userModelWithoutRoles(is_string($model) ? $model : 'auth.providers.users.model');
        }

        return $model;
    }

    /**
     * Пользователи с ролями; с ролью $hiddenRole в выборку не попадают.
     *
     * @return Builder<Model&HasRolesInterface>
     */
    public function query(?string $hiddenRole): Builder
    {
        $query = $this->modelClass()::query()->with('roles');

        if ($hiddenRole === null) {
            return $query;
        }

        return $query->whereDoesntHave('roles', fn (Builder $roles): Builder => $roles->where('name', $hiddenRole));
    }

    /**
     * Фильтр списка: пользователи хотя бы с одной из ролей; пустой список ролей — без фильтра.
     *
     * @param  Builder<Model>     $query
     * @param  array<int, string> $roles
     * @return Builder<Model>
     */
    public function withAnyRole(Builder $query, array $roles): Builder
    {
        if ($roles === []) {
            return $query;
        }

        return $query->whereHas('roles', fn (Builder $role): Builder => $role->whereIn('name', $roles));
    }

    /**
     * @param array<string, string> $attributes
     * @param array<int, string>    $roles
     */
    public function create(array $attributes, array $roles): Model&HasRolesInterface
    {
        $user = $this->modelClass()::query()->create($attributes);
        $user->syncRoles($roles);

        return $user;
    }

    /**
     * @param array<string, string> $attributes
     * @param array<int, string>    $roles
     */
    public function update(Model&HasRolesInterface $user, array $attributes, array $roles): Model&HasRolesInterface
    {
        $user->update($attributes);
        $user->syncRoles($roles);

        return $user;
    }
}
