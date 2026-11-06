<?php

namespace Zuber\Kit\Modules\Users\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;

/**
 * Таблица ролей spatie/laravel-permission: модель — из его конфига.
 */
final readonly class RoleRepository
{
    public function __construct(private PermissionRegistrar $permissions) {}

    /**
     * @return class-string<Model&Role>
     */
    public function modelClass(): string
    {
        /** @var class-string<Model&Role> */
        return $this->permissions->getRoleClass();
    }

    /**
     * Роли с правами и числом пользователей; роль $hiddenRole в выборку не попадает.
     *
     * @return Builder<Model&Role>
     */
    public function query(?string $hiddenRole): Builder
    {
        $query = $this->modelClass()::query()->with('permissions')->withCount('users');

        if ($hiddenRole === null) {
            return $query;
        }

        return $query->where('name', '!=', $hiddenRole);
    }

    /**
     * Имена ролей в порядке создания (сначала роли из кода); роль $hiddenRole не попадает.
     *
     * @return array<int, string>
     */
    public function names(?string $hiddenRole): array
    {
        /** @var array<int, string> */
        return $this->modelClass()::query()
            ->when($hiddenRole !== null, fn (Builder $query): Builder => $query->where('name', '!=', $hiddenRole))
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }

    /**
     * Роли из списка, которых нет в базе.
     *
     * @param  array<int, string> $names
     * @return array<int, string>
     */
    public function missing(array $names): array
    {
        /** @var array<int, string> $existing */
        $existing = $this->modelClass()::query()->whereIn('name', $names)->pluck('name')->all();

        return collect($names)->diff($existing)->values()->all();
    }

    public function nameTaken(string $name, ?Role $except = null): bool
    {
        return $this->modelClass()::query()
            ->where('name', $name)
            ->when($except !== null, fn (Builder $query): Builder => $query->whereKeyNot($except?->getKey()))
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public function permissionNames(Role $role): array
    {
        /** @var array<int, string> */
        return $role->permissions()->pluck('name')->all();
    }

    public function usersCount(Role $role): int
    {
        return $role->users()->count();
    }

    /**
     * @param array<int, string> $permissions
     */
    public function create(string $name, array $permissions): Model&Role
    {
        $role = $this->modelClass()::query()->create(['name' => $name, 'guard_name' => $this->guard()]);
        $role->syncPermissions($permissions);
        $this->permissions->forgetCachedPermissions();

        return $role;
    }

    /**
     * @param array<int, string> $permissions
     */
    public function update(Model&Role $role, string $name, array $permissions): Model&Role
    {
        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);
        $this->permissions->forgetCachedPermissions();

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
        $this->permissions->forgetCachedPermissions();
    }

    /**
     * Guard ролей, которые заводят в админке, — как у ролей из kit:sync-roles (findOrCreate без guard).
     */
    private function guard(): string
    {
        return Guard::getDefaultName($this->modelClass());
    }
}
