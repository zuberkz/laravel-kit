<?php

use Zuber\Kit\Support\PhoneSupport;

it('normalizes any way of writing a number', function (?string $phone, ?string $normalized): void {
    expect(PhoneSupport::normalize($phone))->toBe($normalized);
})->with([
    'masked' => ['+7 (707) 112-25-36', '+77071122536'],
    'with 8' => ['8 707 112 25 36', '+77071122536'],
    'ten digits' => ['7071122536', '+77071122536'],
    'too short' => ['+7 707 112', null],
    'empty' => [null, null],
]);

it('formats a stored number for the screen', function (): void {
    expect(PhoneSupport::format('+77071122536'))->toBe('+7 (707) 112-25-36')
        ->and(PhoneSupport::format('не номер'))->toBe('не номер')
        ->and(PhoneSupport::format(null))->toBeNull();
});
