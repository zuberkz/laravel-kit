<?php

namespace Zuber\Kit\Modules\Access\Repositories;

use Spatie\Permission\PermissionRegistrar;

/**
 * Таблицы ролей и прав spatie/laravel-permission для синхронизации с кодом.
 */
final readonly class AccessRepository
{
    public function __construct(private PermissionRegistrar $registrar) {}

    public function ensurePermission(string $name): void
    {
        $this->registrar->getPermissionClass()::findOrCreate($name);
    }

    /**
     * @param array<int, string> $permissions
     */
    public function syncRole(string $name, array $permissions): void
    {
        $role = $this->registrar->getRoleClass()::findOrCreate($name);
        $role->syncPermissions($permissions);
    }

    /**
     * @return array<int, string>
     */
    public function roleNames(): array
    {
        /** @var array<int, string> */
        return $this->registrar->getRoleClass()::query()->pluck('name')->all();
    }

    /**
     * spatie кэширует список прав: после изменений его нужно сбросить, иначе роли не найдут новые права.
     */
    public function forgetCache(): void
    {
        $this->registrar->forgetCachedPermissions();
    }
}
