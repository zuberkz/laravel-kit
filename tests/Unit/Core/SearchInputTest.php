<?php

use Zuber\Kit\Core\Filament\Forms\Fields\SearchInput;

it('is a search field that searches as you type with a pause', function (): void {
    $field = SearchInput::make();

    expect($field->getName())->toBe('search')
        ->and($field->getType())->toBe('search')
        ->and($field->isLive())->toBeTrue()
        ->and($field->getLiveDebounce())->toBe(400)
        ->and($field->getMaxLength())->toBe(100);
});
