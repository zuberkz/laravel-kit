<?php

namespace Zuber\Kit\Modules\Users\Data;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * Настройки разделов «Пользователи» и «Роли» из UsersPlugin: роли по умолчанию и права проекта
 * (enum) и кто управляет пользователями. Роли из enum — системные: подпись и описание из кода, имя
 * не меняется; остальные роли заводят в админке, их подпись — имя.
 */
final readonly class UsersSettingsData
{
    /**
     * @param class-string<BackedEnum&HasLabel>      $roles            RoleEnum проекта; HasDescription — описания ролей в форме
     * @param string|null                            $superAdminRole   роль разработчика: видна и выдаётся только супер-администраторам
     * @param string|null                            $managePermission право на раздел; null — раздел открыт всем, кто вошёл в панель
     * @param class-string<BackedEnum&HasLabel>|null $permissions      PermissionEnum проекта — галочки прав в разделе «Роли»
     */
    public function __construct(
        public string $roles,
        public ?string $superAdminRole = null,
        public ?string $managePermission = null,
        public ?string $permissions = null,
        public string|UnitEnum|null $navigationGroup = null,
    ) {}

    /**
     * Роли в порядке enum: имя роли => подпись.
     *
     * @return array<string, string>
     */
    public function roleLabels(): array
    {
        return collect($this->roles::cases())
            ->mapWithKeys(fn (BackedEnum&HasLabel $role): array => [(string) $role->value => $this->label($role)])
            ->all();
    }

    /**
     * Описания ролей, у которых enum их даёт (HasDescription): имя роли => описание.
     *
     * @return array<string, string>
     */
    public function roleDescriptions(): array
    {
        $descriptions = [];

        foreach ($this->roles::cases() as $role) {
            if ($role instanceof HasDescription) {
                $descriptions[(string) $role->value] = $this->text($role->getDescription());
            }
        }

        return $descriptions;
    }

    public function isCodeRole(string $name): bool
    {
        return array_key_exists($name, $this->roleLabels());
    }

    public function roleLabel(string $name): string
    {
        return $this->roleLabels()[$name] ?? $name;
    }

    public function roleDescription(string $name): ?string
    {
        return $this->roleDescriptions()[$name] ?? null;
    }

    public function permissionLabel(string $name): string
    {
        $permission = $this->permissions === null ? null : $this->permissions::tryFrom($name);

        return $permission === null ? $name : $this->label($permission);
    }

    /**
     * Права в порядке enum: имя права => подпись.
     *
     * @return array<string, string>
     */
    public function permissionOptions(): array
    {
        if ($this->permissions === null) {
            return [];
        }

        return collect($this->permissions::cases())
            ->mapWithKeys(fn (BackedEnum&HasLabel $permission): array => [(string) $permission->value => $this->label($permission)])
            ->all();
    }

    /**
     * Описания прав, у которых enum их даёт (HasDescription): имя права => описание.
     *
     * @return array<string, string>
     */
    public function permissionDescriptions(): array
    {
        if ($this->permissions === null) {
            return [];
        }

        return collect($this->permissions::cases())
            ->filter(fn (BackedEnum $permission): bool => $permission instanceof HasDescription)
            ->mapWithKeys(fn (BackedEnum&HasDescription $permission): array => [(string) $permission->value => $this->text($permission->getDescription())])
            ->all();
    }

    private function label(HasLabel $case): string
    {
        return $this->text($case->getLabel());
    }

    private function text(string|Htmlable|null $value): string
    {
        return $value instanceof Htmlable ? $value->toHtml() : (string) $value;
    }
}
