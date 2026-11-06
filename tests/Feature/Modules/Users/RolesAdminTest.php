<?php

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages\CreateRole;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages\EditRole;
use Zuber\Kit\Modules\Users\Filament\Resources\Roles\Pages\ListRoles;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\Pages\CreateUser;
use Zuber\Kit\Tests\Fixtures\PermissionEnum;
use Zuber\Kit\Tests\Fixtures\RoleEnum;
use Zuber\Kit\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->seedRoles();
    Filament::setCurrentPanel('panel');
});

/**
 * @param array<int, PermissionEnum> $permissions
 */
function customRole(string $name, array $permissions): Role
{
    $role = Role::findOrCreate($name);
    $role->syncPermissions(collect($permissions)->map(fn (PermissionEnum $permission): string => $permission->value)->all());

    return $role;
}

/**
 * @return array<int, string>
 */
function permissionsOf(string $role): array
{
    return Role::findByName($role)->permissions->pluck('name')->sort()->values()->all();
}

it('creates a role with the checked permissions and offers it for users', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));

    Livewire::test(CreateRole::class)
        ->assertSee(['Вход в админку', 'Управление пользователями', 'Заявки'])
        ->fillForm(['name' => 'Контент-менеджер', 'permissions' => [PermissionEnum::ACCESS_ADMIN->value, PermissionEnum::MANAGE_LEADS->value]])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(permissionsOf('Контент-менеджер'))->toBe([PermissionEnum::ACCESS_ADMIN->value, PermissionEnum::MANAGE_LEADS->value]);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Айгерим', 'email' => 'aigerim@example.com', 'password' => 'Kp7m-Xw3q-Rt9z', 'roles' => ['Контент-менеджер']])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'aigerim@example.com')->sole()->hasPermissionTo(PermissionEnum::MANAGE_LEADS->value))->toBeTrue();
});

it('validates the role name', function (string $name, string $error): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => $name])
        ->call('create')
        ->assertHasFormErrors(['name' => $error]);
})->with([
    'empty' => ['', 'required'],
    'taken by a code role' => [RoleEnum::MANAGER->value, 'unique'],
]);

it('renames a role and changes its permissions', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));
    $role = customRole('Контент', [PermissionEnum::ACCESS_ADMIN]);

    Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
        ->assertSchemaStateSet(['name' => 'Контент', 'permissions' => [PermissionEnum::ACCESS_ADMIN->value]])
        ->fillForm(['name' => 'Контент-менеджер', 'permissions' => [PermissionEnum::MANAGE_LEADS->value]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($role->fresh()?->name)->toBe('Контент-менеджер')
        ->and(permissionsOf('Контент-менеджер'))->toBe([PermissionEnum::MANAGE_LEADS->value]);
});

it('changes the permissions of a code role but keeps its name', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));
    $manager = Role::findByName(RoleEnum::MANAGER->value);

    Livewire::test(EditRole::class, ['record' => $manager->getRouteKey()])
        ->assertSchemaStateSet(['name' => 'Менеджер'])
        ->assertFormFieldDisabled('name')
        ->fillForm(['permissions' => [PermissionEnum::ACCESS_ADMIN->value, PermissionEnum::MANAGE_LEADS->value]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($manager->fresh()?->name)->toBe(RoleEnum::MANAGER->value)
        ->and(permissionsOf(RoleEnum::MANAGER->value))->toBe([PermissionEnum::ACCESS_ADMIN->value, PermissionEnum::MANAGE_LEADS->value]);
});

it('does not let anyone edit the super admin role', function (): void {
    $superAdmin = Role::findByName(RoleEnum::SUPER_ADMIN->value);

    $this->actingAs($this->userWithRole(RoleEnum::SUPER_ADMIN))
        ->get("/panel/roles/{$superAdmin->id}/edit")
        ->assertForbidden();

    $this->actingAs($this->userWithRole(RoleEnum::ADMIN))
        ->get("/panel/roles/{$superAdmin->id}/edit")
        ->assertNotFound();
});

it('does not let a user grant or take away a permission they do not have', function (): void {
    $hr = customRole('Кадровик', [PermissionEnum::ACCESS_ADMIN, PermissionEnum::MANAGE_USERS]);
    $this->actingAs(User::query()->create(['name' => 'Кадровик', 'email' => 'hr@example.com', 'password' => 'secret-password'])->assignRole($hr));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Продажи', 'permissions' => [PermissionEnum::MANAGE_LEADS->value]])
        ->call('create')
        ->assertNotified('Выдать или забрать можно только права, которые есть у вас. Нет у вас: Заявки.');

    expect(Role::query()->where('name', 'Продажи')->exists())->toBeFalse();

    $admin = Role::findByName(RoleEnum::ADMIN->value);

    Livewire::test(EditRole::class, ['record' => $admin->getRouteKey()])
        ->fillForm(['permissions' => [PermissionEnum::ACCESS_ADMIN->value, PermissionEnum::MANAGE_USERS->value]])
        ->call('save')
        ->assertNotified('Выдать или забрать можно только права, которые есть у вас. Нет у вас: Заявки.');

    expect(permissionsOf(RoleEnum::ADMIN->value))->toContain(PermissionEnum::MANAGE_LEADS->value);

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Вход', 'permissions' => [PermissionEnum::ACCESS_ADMIN->value]])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(permissionsOf('Вход'))->toBe([PermissionEnum::ACCESS_ADMIN->value]);
});

it('deletes a role nobody has, but not a role with users or a code role', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));
    $unused = customRole('Стажёр', []);
    $taken = customRole('Продажи', [PermissionEnum::ACCESS_ADMIN]);
    User::query()->create(['name' => 'Продавец', 'email' => 'sales@example.com', 'password' => 'secret-password'])->assignRole($taken);
    $manager = Role::findByName(RoleEnum::MANAGER->value);

    Livewire::test(ListRoles::class)
        ->assertActionHidden(TestAction::make('delete')->table($manager))
        ->callAction(TestAction::make('delete')->table($taken))
        ->assertNotified('Роль «Продажи» выдана пользователям (1). Сначала смените им роль.')
        ->callAction(TestAction::make('delete')->table($unused));

    expect(Role::query()->where('name', 'Стажёр')->exists())->toBeFalse()
        ->and(Role::query()->where('name', 'Продажи')->exists())->toBeTrue();
});
