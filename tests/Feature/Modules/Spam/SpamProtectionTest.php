<?php

use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Zuber\Kit\Tests\Fixtures\ContactForm;

it('accepts a form filled in like a human and stores the phone as +7XXXXXXXXXX', function (): void {
    $form = Livewire::test(ContactForm::class)->fillForm(['name' => 'Айгерим', 'phone' => '+7 (707) 112-25-36']);
    $this->travel(5)->seconds();

    $form->call('submit')->assertHasNoFormErrors()->assertSet('thanked', true);

    expect($form->get('sent'))->toBe(['name' => 'Айгерим', 'phone' => '+77071122536']);
});

it('thanks a bot like a human and keeps nothing', function (array $data, int $seconds): void {
    $form = Livewire::test(ContactForm::class)->fillForm(['name' => 'Bot', 'phone' => '+7 (707) 112-25-36', ...$data]);
    $this->travel($seconds)->seconds();

    $form->call('submit')->assertSet('thanked', true)->assertSet('sent', null);
})->with([
    'filled the honeypot' => [['website' => 'http://spam.example'], 5],
    'submitted too fast' => [[], 1],
]);

it('does not let the client change when the form was opened', function (): void {
    Livewire::test(ContactForm::class)->set('spamFormStartedAt', 1);
})->throws(CannotUpdateLockedPropertyException::class);

it('asks for the whole phone number', function (): void {
    Livewire::test(ContactForm::class)
        ->fillForm(['name' => 'Айгерим', 'phone' => '+7 (707) 11'])
        ->call('submit')
        ->assertHasFormErrors(['phone' => 'regex']);
});
