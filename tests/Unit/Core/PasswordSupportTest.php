<?php

use Zuber\Kit\Core\Support\PasswordSupport;

it('generates three dash-separated groups without look-alike characters', function (): void {
    collect(range(1, 50))->each(function (): void {
        expect(PasswordSupport::generate())
            ->toMatch('/^[a-km-np-zA-HJ-NP-Z2-9]{4}(-[a-km-np-zA-HJ-NP-Z2-9]{4}){2}$/')
            ->toMatch('/[a-z]/')
            ->toMatch('/[A-Z]/')
            ->toMatch('/\d/');
    });
});

it('generates a different password every time', function (): void {
    $passwords = collect(range(1, 50))->map(fn (): string => PasswordSupport::generate());

    expect($passwords->unique())->toHaveCount(50);
});
