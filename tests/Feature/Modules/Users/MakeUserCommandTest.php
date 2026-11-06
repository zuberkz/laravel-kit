<?php

use Illuminate\Support\Facades\Hash;
use Zuber\Kit\Tests\Fixtures\User;

beforeEach(function (): void {
    $this->seedRoles();
});

it('creates an admin with a generated password shown once', function (): void {
    $this->artisan('kit:make-user', ['email' => 'Owner@Example.kz', '--role' => ['admin']])
        ->expectsOutputToContain('Пользователь owner@example.kz создан')
        ->expectsOutputToContain('Пароль (показывается один раз)')
        ->assertSuccessful();

    $user = User::query()->where('email', 'owner@example.kz')->sole();

    expect($user->name)->toBe('owner')
        ->and($user->getRoleNames()->all())->toBe(['admin'])
        ->and($user->password)->not->toBe('');
});

it('leaves an existing user alone unless a new password is asked for', function (): void {
    $user = User::query()->create(['name' => 'Владелец', 'email' => 'owner@example.kz', 'password' => Hash::make('old-password')]);

    $this->artisan('kit:make-user', ['email' => 'owner@example.kz', '--role' => ['admin']])
        ->expectsOutputToContain('уже есть — ничего не меняю')
        ->doesntExpectOutputToContain('Пароль')
        ->assertSuccessful();
    expect(Hash::check('old-password', (string) $user->fresh()?->password))->toBeTrue()
        ->and($user->fresh()?->getRoleNames()->all())->toBe([]);

    $this->artisan('kit:make-user', ['email' => 'owner@example.kz', '--reset-password' => true])
        ->expectsOutputToContain('выдан новый пароль')
        ->assertSuccessful();
    expect(Hash::check('old-password', (string) $user->fresh()?->password))->toBeFalse()
        ->and(User::query()->count())->toBe(1);
});

it('creates a user with a typed password and never prints it', function (): void {
    $this->artisan('kit:make-user', ['email' => 'owner@example.kz', '--role' => ['admin'], '--ask-password' => true])
        ->expectsQuestion('Пароль', 'typed-secret-1')
        ->expectsQuestion('Пароль ещё раз', 'typed-secret-1')
        ->expectsOutputToContain('Пользователь owner@example.kz создан')
        ->doesntExpectOutputToContain('typed-secret-1')
        ->doesntExpectOutputToContain('показывается один раз')
        ->assertSuccessful();

    expect(Hash::check('typed-secret-1', (string) User::query()->where('email', 'owner@example.kz')->sole()->password))->toBeTrue();
});

it('changes the password of an existing user to a typed one', function (): void {
    $user = User::query()->create(['name' => 'Владелец', 'email' => 'owner@example.kz', 'password' => Hash::make('old-password')]);

    $this->artisan('kit:make-user', ['email' => 'owner@example.kz', '--ask-password' => true])
        ->expectsQuestion('Пароль', 'typed-secret-2')
        ->expectsQuestion('Пароль ещё раз', 'typed-secret-2')
        ->expectsOutputToContain('Пароль пользователя owner@example.kz изменён')
        ->doesntExpectOutputToContain('typed-secret-2')
        ->assertSuccessful();

    expect(Hash::check('typed-secret-2', (string) $user->fresh()?->password))->toBeTrue();
});

it('changes nothing when the typed passwords differ', function (): void {
    $user = User::query()->create(['name' => 'Владелец', 'email' => 'owner@example.kz', 'password' => Hash::make('old-password')]);

    $this->artisan('kit:make-user', ['email' => 'owner@example.kz', '--ask-password' => true])
        ->expectsQuestion('Пароль', 'typed-secret-3')
        ->expectsQuestion('Пароль ещё раз', 'another-secret')
        ->expectsOutputToContain('Пароли не совпадают')
        ->assertFailed();

    expect(Hash::check('old-password', (string) $user->fresh()?->password))->toBeTrue();
});

it('refuses a user it could not create properly', function (array $arguments, string $error, int $code): void {
    $this->artisan('kit:make-user', $arguments)->expectsOutputToContain($error)->assertExitCode($code);

    expect(User::query()->count())->toBe(0);
})->with([
    'no role' => [['email' => 'owner@example.kz'], 'нужна роль', 1],
    'unknown role' => [['email' => 'owner@example.kz', '--role' => ['owner']], 'Ролей owner нет', 1],
    'not an email' => [['email' => 'owner'], 'не похожа на адрес', 2],
]);
