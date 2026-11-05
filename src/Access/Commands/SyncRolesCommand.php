<?php

namespace Zuber\Kit\Access\Commands;

use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Zuber\Kit\Access\Actions\SyncRolesAction;
use Zuber\Kit\Access\Contracts\RoleEnumInterface;
use Zuber\Kit\Access\Exceptions\RoleEnumException;

/**
 * Запускается общим деплоем после миграций. В проекте без enum ролей ничего не делает.
 */
final class SyncRolesCommand extends Command
{
    protected $signature = 'kit:sync-roles';

    protected $description = 'Привести роли и права в базе к enum ролей проекта (kit.access.roles)';

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

        $result = $sync->handle($roles);

        $this->components->info(sprintf('Ролей: %d, прав: %d — база совпадает с %s.', $result->roles, $result->permissions, class_basename($roles)));

        if ($result->staleRoles !== []) {
            $this->components->warn('В базе есть роли, которых нет в коде (не удалены): ' . implode(', ', $result->staleRoles));
        }

        return self::SUCCESS;
    }
}
