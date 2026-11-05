<?php

use Filament\Facades\Filament;
use Livewire\Livewire;
use Zuber\Kit\Tests\Fixtures\RoleEnum;
use Zuber\Kit\Users\Filament\Resources\Roles\Pages\ListRoles;
use Zuber\Kit\Users\Filament\Resources\Users\Pages\EditUser;
use Zuber\Kit\Users\Filament\Resources\Users\Pages\ListUsers;

beforeEach(function (): void {
    $this->seedRoles();
    Filament::setCurrentPanel('panel');
});

it('opens the users and roles sections only for those who manage users', function (RoleEnum $role, int $status): void {
    $user = $this->userWithRole($role);

    $this->actingAs($user)->get('/panel/users')->assertStatus($status);
    $this->actingAs($user)->get('/panel/roles')->assertStatus($status);
})->with([
    'manager' => [RoleEnum::MANAGER, 403],
    'admin' => [RoleEnum::ADMIN, 200],
    'super admin' => [RoleEnum::SUPER_ADMIN, 200],
]);

it('hides the super admin from an admin, in the list and by a direct link', function (): void {
    $admin = $this->userWithRole(RoleEnum::ADMIN);
    $developer = $this->userWithRole(RoleEnum::SUPER_ADMIN);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$admin])
        ->assertCanNotSeeTableRecords([$developer]);

    $this->get("/panel/users/{$developer->id}/edit")->assertNotFound();
});

it('shows the super admin to another super admin', function (): void {
    $developer = $this->userWithRole(RoleEnum::SUPER_ADMIN);
    $other = $this->userWithRole(RoleEnum::SUPER_ADMIN);

    $this->actingAs($developer);

    Livewire::test(ListUsers::class)->assertCanSeeTableRecords([$developer, $other]);
});

it('does not let a user delete themselves', function (): void {
    $admin = $this->userWithRole(RoleEnum::ADMIN);
    $manager = $this->userWithRole(RoleEnum::MANAGER);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertActionHidden(\Filament\Actions\Testing\TestAction::make('delete')->table($admin))
        ->assertActionVisible(\Filament\Actions\Testing\TestAction::make('delete')->table($manager));

    Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])->assertActionHidden('delete');
});

it('lists roles with labels, permissions and user counts, without the super admin role for an admin', function (): void {
    $admin = $this->userWithRole(RoleEnum::ADMIN);
    $this->userWithRole(RoleEnum::MANAGER);

    $this->actingAs($admin);

    Livewire::test(ListRoles::class)
        ->assertSee(['Администратор', 'Менеджер', 'Управление пользователями'])
        ->assertDontSee('Супер-администратор')
        ->assertCountTableRecords(2);
});
