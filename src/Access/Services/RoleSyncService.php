<?php

namespace Zuber\Kit\Access\Services;

use BackedEnum;
use Zuber\Kit\Access\Contracts\RoleEnumInterface;
use Zuber\Kit\Access\Data\RoleSyncResultData;
use Zuber\Kit\Access\Repositories\AccessRepository;

/**
 * Роли и права в базе — по enum ролей проекта: права ролей синхронизируются, ничего не удаляется.
 */
final readonly class RoleSyncService
{
    public function __construct(private AccessRepository $access) {}

    /**
     * @param class-string<BackedEnum&RoleEnumInterface> $roles
     */
    public function sync(string $roles): RoleSyncResultData
    {
        $cases = collect($roles::cases());
        $permissions = $cases
            ->flatMap(fn (RoleEnumInterface $role): array => self::names($role->permissions()))
            ->unique()
            ->values()
            ->all();

        $this->access->forgetCache();
        collect($permissions)->each(fn (string $permission) => $this->access->ensurePermission($permission));

        // findOrCreate закэшировал список прав до создания новых: без сброса роли их не найдут
        $this->access->forgetCache();
        $cases->each(fn (BackedEnum&RoleEnumInterface $role) => $this->access->syncRole((string) $role->value, self::names($role->permissions())));
        $this->access->forgetCache();

        $known = self::names($cases->all());

        return new RoleSyncResultData(
            roles: count($known),
            permissions: count($permissions),
            staleRoles: collect($this->access->roleNames())->diff($known)->values()->all(),
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
