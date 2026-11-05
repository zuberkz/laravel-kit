<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Zuber\Kit\Access\Exceptions\RoleEnumException;
use Zuber\Kit\Tests\Fixtures\PermissionEnum;
use Zuber\Kit\Tests\Fixtures\RoleEnum;

it('creates the roles with exactly the permissions from the enum', function (): void {
    $this->artisan('kit:sync-roles')->assertSuccessful();

    collect(RoleEnum::cases())->each(function (RoleEnum $role): void {
        expect(Role::findByName($role->value)->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($role->permissions())->map(fn (PermissionEnum $permission): string => $permission->value)->sort()->values()->all());
    });

    expect(Permission::query()->count())->toBe(count(PermissionEnum::cases()));
});

it('can run again and takes away a permission removed from a role', function (): void {
    $this->artisan('kit:sync-roles')->assertSuccessful();
    Role::findByName(RoleEnum::MANAGER->value)->givePermissionTo(PermissionEnum::MANAGE_USERS->value);

    $this->artisan('kit:sync-roles')->assertSuccessful();

    expect(Role::query()->count())->toBe(count(RoleEnum::cases()))
        ->and(Role::findByName(RoleEnum::MANAGER->value)->hasPermissionTo(PermissionEnum::MANAGE_USERS->value))->toBeFalse();
});

it('warns about roles that are no longer in the code but keeps them', function (): void {
    Role::findOrCreate('editor');

    $this->artisan('kit:sync-roles')
        ->expectsOutputToContain('editor')
        ->assertSuccessful();

    expect(Role::query()->where('name', 'editor')->exists())->toBeTrue();
});

it('does nothing in a project without a roles enum', function (): void {
    config(['kit.access.roles' => 'App\\Enums\\Access\\RoleEnum']);

    $this->artisan('kit:sync-roles')->assertSuccessful();

    expect(Role::query()->count())->toBe(0);
});

it('refuses an enum without permissions()', function (): void {
    config(['kit.access.roles' => PermissionEnum::class]);

    $this->artisan('kit:sync-roles');
})->throws(RoleEnumException::class);
