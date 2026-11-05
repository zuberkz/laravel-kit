<?php

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\Pages\CreateUser;
use Zuber\Kit\Modules\Users\Filament\Resources\Users\Pages\EditUser;
use Zuber\Kit\Tests\Fixtures\RoleEnum;
use Zuber\Kit\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->seedRoles();
    Filament::setCurrentPanel('panel');
});

it('creates a user with a hashed password and roles', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Айгерим',
            'email' => 'aigerim@example.com',
            'password' => 'Kp7m-Xw3q-Rt9z',
            'roles' => [RoleEnum::MANAGER->value],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'aigerim@example.com')->firstOrFail();

    expect($user->name)->toBe('Айгерим')
        ->and(Hash::check('Kp7m-Xw3q-Rt9z', $user->password))->toBeTrue()
        ->and($user->getRoleNames()->all())->toBe([RoleEnum::MANAGER->value]);
});

it('validates the user form', function (array $data, array $errors): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN, 'taken@example.com'));

    Livewire::test(CreateUser::class)
        ->fillForm([...['name' => 'Имя', 'email' => 'new@example.com', 'password' => 'Kp7m-Xw3q-Rt9z'], ...$data])
        ->call('create')
        ->assertHasFormErrors($errors);

    expect(User::query()->where('email', 'new@example.com')->exists())->toBeFalse();
})->with([
    'no name' => [['name' => ''], ['name' => 'required']],
    'taken email' => [['email' => 'taken@example.com'], ['email' => 'unique']],
    'no password' => [['password' => ''], ['password' => 'required']],
    'short password' => [['password' => 'short'], ['password']],
]);

it('generates a password into the field and reveals it', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));

    $page = Livewire::test(CreateUser::class)
        ->callAction(TestAction::make('generatePassword')->schemaComponent('password', 'form'))
        ->assertDispatched('kit-password-generated')
        ->assertNotified('Пароль сгенерирован');

    $page->assertActionExists(TestAction::make('copy')->schemaComponent('password', 'form'));

    expect($page->get('data.password'))->toMatch('/^[a-km-np-zA-HJ-NP-Z2-9]{4}(-[a-km-np-zA-HJ-NP-Z2-9]{4}){2}$/');
});

it('does not offer the super admin role to an admin and refuses it if sent anyway', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));

    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('roles', fn ($field): bool => ! array_key_exists(RoleEnum::SUPER_ADMIN->value, $field->getOptions()))
        ->fillForm([
            'name' => 'Взломщик',
            'email' => 'hacker@example.com',
            'password' => 'Kp7m-Xw3q-Rt9z',
            'roles' => [RoleEnum::SUPER_ADMIN->value],
        ])
        ->call('create');

    expect(User::query()->where('email', 'hacker@example.com')->exists())->toBeFalse();
});

it('lets a super admin grant the super admin role', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::SUPER_ADMIN));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Второй разработчик',
            'email' => 'dev2@example.com',
            'password' => 'Kp7m-Xw3q-Rt9z',
            'roles' => [RoleEnum::SUPER_ADMIN->value],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'dev2@example.com')->firstOrFail()->hasRole(RoleEnum::SUPER_ADMIN))->toBeTrue();
});

it('keeps the password when the field is left empty and never fills it into the form', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));
    $manager = $this->userWithRole(RoleEnum::MANAGER);
    $hash = $manager->password;

    Livewire::test(EditUser::class, ['record' => $manager->getRouteKey()])
        ->assertFormSet(['password' => null, 'roles' => [RoleEnum::MANAGER->value]])
        ->fillForm(['name' => 'Новое имя', 'roles' => [RoleEnum::ADMIN->value]])
        ->call('save')
        ->assertHasNoFormErrors();

    $manager->refresh();

    expect($manager->name)->toBe('Новое имя')
        ->and($manager->password)->toBe($hash)
        ->and($manager->getRoleNames()->all())->toBe([RoleEnum::ADMIN->value]);
});

it('changes the password when a new one is entered', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));
    $manager = $this->userWithRole(RoleEnum::MANAGER);

    Livewire::test(EditUser::class, ['record' => $manager->getRouteKey()])
        ->fillForm(['password' => 'Nw4r-Pq8s-Ty3v'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('Nw4r-Pq8s-Ty3v', $manager->refresh()->password))->toBeTrue();
});

it('explains the chosen roles under the roles field', function (): void {
    $this->actingAs($this->userWithRole(RoleEnum::ADMIN));

    Livewire::test(CreateUser::class)
        ->assertDontSee('Без доступа к пользователям')
        ->fillForm(['roles' => [RoleEnum::MANAGER->value]])
        ->assertSee('Без доступа к пользователям');
});
