<?php

namespace Zuber\Kit\Modules\Access\Commands;

use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Zuber\Kit\Modules\Access\Actions\SyncRolesAction;
use Zuber\Kit\Modules\Access\Contracts\RoleEnumInterface;
use Zuber\Kit\Modules\Access\Exceptions\RoleEnumException;

/**
 * Запускается общим деплоем после миграций. В проекте без enum ролей ничего не делает.
 */
final class SyncRolesCommand extends Command
{
    protected $signature = 'kit:sync-roles';

    protected $description = 'Создать права из кода и недостающие роли (kit.access), права существующих ролей не трогать';

    public function handle(SyncRolesAction $sync, Repository $config): int
    {
        $roles = $config->get('kit.access.roles');

        if (! is_string($roles) || ! enum_exists($roles)) {
            $this->components->info('Enum ролей нет — синхронизировать нечего.');

            return self::SUCCESS;
        }

        if (! is_subclass_of($roles, BackedEnum::class) || ! is_subclass_of($roles, RoleEnumInterface::class)) {
            throw RoleEnumException::invalid($roles);
        }

        $superAdminRole = $config->get('kit.access.super_admin');
        $result = $sync->handle($roles, $this->permissions($config), is_string($superAdminRole) && $superAdminRole !== '' ? $superAdminRole : null);

        $this->components->info(sprintf('Прав в коде: %d, ролей по умолчанию: %d.', $result->permissions, $result->roles));

        if ($result->createdRoles !== []) {
            $this->components->info('Созданы роли: ' . implode(', ', $result->createdRoles));
        }

        if ($result->stalePermissions !== []) {
            $this->components->warn('В базе есть права, которых нет в коде (не удалены): ' . implode(', ', $result->stalePermissions));
        }

        return self::SUCCESS;
    }

    /**
     * @return class-string<BackedEnum>|null
     */
    private function permissions(Repository $config): ?string
    {
        $permissions = $config->get('kit.access.permissions');

        if (! is_string($permissions) || ! enum_exists($permissions)) {
            return null;
        }

        if (! is_subclass_of($permissions, BackedEnum::class)) {
            throw RoleEnumException::invalidPermissions($permissions);
        }

        return $permissions;
    }
}
