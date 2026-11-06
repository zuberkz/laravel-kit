<?php

namespace Zuber\Kit\Modules\Access\Services;

use BackedEnum;
use Zuber\Kit\Modules\Access\Contracts\RoleEnumInterface;
use Zuber\Kit\Modules\Access\Data\RoleSyncResultData;
use Zuber\Kit\Modules\Access\Repositories\AccessRepository;

/**
 * Права в базе — по коду проекта, роли — данные админки. Синхронизация создаёт права из
 * PermissionEnum и недостающие роли RoleEnum с правами по умолчанию; права существующих ролей
 * правят в админке, и деплой их не трогает. Исключение — супер-администратор: у него всегда все
 * права из кода, иначе новое право не дошло бы до разработчика.
 */
final readonly class RoleSyncService
{
    public function __construct(private AccessRepository $access) {}

    /**
     * @param class-string<BackedEnum&RoleEnumInterface> $roles
     * @param class-string<BackedEnum>|null              $permissions    PermissionEnum; null — права только из ролей по умолчанию
     * @param string|null                                $superAdminRole роль, которой всегда выдаются все права
     */
    public function sync(string $roles, ?string $permissions = null, ?string $superAdminRole = null): RoleSyncResultData
    {
        $cases = collect($roles::cases());
        $codePermissions = collect($permissions === null ? [] : self::names($permissions::cases()))
            ->merge($cases->flatMap(fn (RoleEnumInterface $role): array => self::names($role->permissions())))
            ->unique()
            ->values()
            ->all();

        $this->access->forgetCache();
        collect($codePermissions)->each(fn (string $permission) => $this->access->ensurePermission($permission));

        // findOrCreate закэшировал список прав до создания новых: без сброса роли их не найдут
        $this->access->forgetCache();
        $missing = $cases
            ->reject(fn (BackedEnum&RoleEnumInterface $role): bool => $this->access->roleExists((string) $role->value))
            ->values();

        $missing
            ->reject(fn (BackedEnum&RoleEnumInterface $role): bool => (string) $role->value === $superAdminRole)
            ->each(fn (BackedEnum&RoleEnumInterface $role) => $this->access->syncRole((string) $role->value, self::names($role->permissions())));

        if ($superAdminRole !== null) {
            $this->access->syncRole($superAdminRole, $codePermissions);
        }

        $this->access->forgetCache();

        return new RoleSyncResultData(
            roles: $cases->count(),
            permissions: count($codePermissions),
            createdRoles: self::names($missing->all()),
            stalePermissions: collect($this->access->permissionNames())->diff($codePermissions)->values()->all(),
        );
    }

    /**
     * @param  array<int, BackedEnum> $cases
     * @return array<int, string>
     */
    private static function names(array $cases): array
    {
        return collect($cases)->map(fn (BackedEnum $case): string => (string) $case->value)->all();
    }
}
