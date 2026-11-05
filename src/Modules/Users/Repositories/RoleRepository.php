<?php

namespace Zuber\Kit\Modules\Users\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

/**
 * Таблица ролей spatie/laravel-permission: модель — из его конфига.
 */
final readonly class RoleRepository
{
    public function __construct(private PermissionRegistrar $permissions) {}

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        /** @var class-string<Model> */
        return $this->permissions->getRoleClass();
    }

    /**
     * Роли с правами и числом пользователей; роль $hiddenRole в выборку не попадает.
     *
     * @return Builder<Model>
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
}
