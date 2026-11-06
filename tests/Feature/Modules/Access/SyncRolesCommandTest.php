<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Zuber\Kit\Modules\Access\Exceptions\RoleEnumException;
use Zuber\Kit\Tests\Fixtures\PermissionEnum;
use Zuber\Kit\Tests\Fixtures\RoleEnum;

/**
 * @return array<int, string>
 */
function rolePermissions(string $role): array
{
    return Role::findByName($role)->permissions->pluck('name')->sort()->values()->all();
}

/**
 * @param  array<int, PermissionEnum> $permissions
 * @return array<int, string>
 */
function permissionNames(array $permissions): array
{
    return collect($permissions)->map(fn (PermissionEnum $permission): string => $permission->value)->sort()->values()->all();
}

it('creates every permission of the code and the roles with their default permissions', function (): void {
    $this->artisan('kit:sync-roles')
        ->expectsOutputToContain('Созданы роли: super_admin, admin, manager')
        ->assertSuccessful();

    collect(RoleEnum::cases())->each(function (RoleEnum $role): void {
        expect(rolePermissions($role->value))->toBe(permissionNames($role->permissions()));
    });

    expect(Permission::query()->count())->toBe(count(PermissionEnum::cases()));
});

it('keeps the permissions set in the admin when it runs again', function (): void {
    $this->artisan('kit:sync-roles')->assertSuccessful();
    Role::findByName(RoleEnum::MANAGER->value)->givePermissionTo(PermissionEnum::MANAGE_LEADS->value);
    Role::findByName(RoleEnum::ADMIN->value)->revokePermissionTo(PermissionEnum::MANAGE_USERS->value);
    Role::findOrCreate('Контент-менеджер')->givePermissionTo(PermissionEnum::ACCESS_ADMIN->value);

    $this->artisan('kit:sync-roles')
        ->doesntExpectOutputToContain('Созданы роли')
        ->assertSuccessful();

    expect(rolePermissions(RoleEnum::MANAGER->value))->toBe(permissionNames([PermissionEnum::ACCESS_ADMIN, PermissionEnum::MANAGE_LEADS]))
        ->and(rolePermissions(RoleEnum::ADMIN->value))->toBe(permissionNames([PermissionEnum::ACCESS_ADMIN, PermissionEnum::MANAGE_LEADS]))
        ->and(rolePermissions('Контент-менеджер'))->toBe([PermissionEnum::ACCESS_ADMIN->value]);
});

it('always gives the super admin every permission of the code', function (): void {
    $this->artisan('kit:sync-roles')->assertSuccessful();
    Role::findByName(RoleEnum::SUPER_ADMIN->value)->syncPermissions([]);

    $this->artisan('kit:sync-roles')->assertSuccessful();

    expect(rolePermissions(RoleEnum::SUPER_ADMIN->value))->toBe(permissionNames(PermissionEnum::cases()));
});

it('warns about permissions that are no longer in the code but keeps them', function (): void {
    Permission::findOrCreate('view_any_service');

    $this->artisan('kit:sync-roles')
        ->expectsOutputToContain('view_any_service')
        ->assertSuccessful();

    expect(Permission::query()->where('name', 'view_any_service')->exists())->toBeTrue();
});

it('takes the permissions from the roles when there is no permissions enum', function (): void {
    config(['kit.access.permissions' => 'App\\Enums\\Access\\PermissionEnum']);

    $this->artisan('kit:sync-roles')->assertSuccessful();

    expect(Permission::query()->count())->toBe(count(PermissionEnum::cases()));
});

it('does nothing in a project without a roles enum', function (): void {
    config(['kit.access.roles' => 'App\\Enums\\Access\\RoleEnum']);

    $this->artisan('kit:sync-roles')->assertSuccessful();

    expect(Role::query()->count())->toBe(0);
});

it('refuses a roles enum without permissions()', function (): void {
    config(['kit.access.roles' => PermissionEnum::class]);

    $this->artisan('kit:sync-roles');
})->throws(RoleEnumException::class);
