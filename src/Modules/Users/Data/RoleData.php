<?php

namespace Zuber\Kit\Modules\Users\Data;

use Illuminate\Support\Arr;

/**
 * Роль из формы раздела «Роли»: имя и права (имена из PermissionEnum).
 */
final readonly class RoleData
{
    /**
     * @param array<int, string> $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
    ) {}

    /**
     * У системной роли поле имени выключено и в форму не приходит — имя остаётся $currentName.
     *
     * @param array<string, mixed> $state
     */
    public static function fromForm(array $state, ?string $currentName = null): self
    {
        $name = $state['name'] ?? $currentName;

        return new self(
            name: trim(is_string($name) ? $name : ''),
            permissions: collect(Arr::array($state, 'permissions'))->filter(fn (mixed $permission): bool => is_string($permission))->values()->all(),
        );
    }
}
