<?php

use Illuminate\Support\Facades\File;

it('adds the domain log channel', function (): void {
    expect(config('logging.channels.domain'))
        ->toMatchArray(['driver' => 'daily', 'level' => 'info', 'max_files' => 30]);
});

it('publishes each part of the kit by its own tag', function (string $tag, string $path): void {
    File::delete(base_path($path));

    $this->artisan('vendor:publish', ['--tag' => $tag, '--force' => true])->assertSuccessful();

    expect(base_path($path))->toBeFile();

    File::delete(base_path($path));
})->with([
    'rules' => ['kit-rules', '.claude/rules/kit/exceptions.md'],
    'arch tests' => ['kit-arch', 'tests/Arch/KitArchTest.php'],
    'pint config' => ['kit-pint', 'pint.json'],
]);
