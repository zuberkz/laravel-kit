<?php

namespace Zuber\Kit\Users\Contracts;

use Illuminate\Support\Collection;

/**
 * Модель пользователя для раздела «Пользователи»: методы, которые даёт трейт
 * Spatie\Permission\Traits\HasRoles. Модель User проекта подключает трейт и объявляет интерфейс.
 */
interface HasRolesInterface
{
    /**
     * @param mixed $roles
     */
    public function hasRole($roles, ?string $guard = null): bool;

    /**
     * В отличие от hasPermissionTo не падает, если права ещё нет в базе.
     *
     * @param mixed $permission
     */
    public function checkPermissionTo($permission, ?string $guardName = null): bool;

    /**
     * @param mixed ...$roles
     */
    public function syncRoles(...$roles): static;

    /**
     * @return Collection<int, string>
     */
    public function getRoleNames(): Collection;
}
