<?php

use Livewire\Livewire;
use Zuber\Kit\Modules\Leads\Livewire\LeadModal;
use Zuber\Kit\Modules\Leads\Models\LeadForm;

it('opens the chosen form in the popup by the event of a #form link', function (): void {
    $form = LeadForm::query()->create(['name' => 'Расчёт уборки', 'fields' => [
        ['type' => 'phone', 'data' => ['key' => 'phone', 'label' => 'Телефон', 'required' => true]],
    ]]);

    Livewire::test(LeadModal::class)
        ->assertSet('open', false)
        ->assertDontSeeLivewire('kit-lead-form')
        ->dispatch('kit-open-lead-form', form: $form->id, source: 'Оставить заявку')
        ->assertSet('open', true)
        ->assertSet('source', 'Оставить заявку')
        ->assertSee('Расчёт уборки')
        ->assertSeeLivewire('kit-lead-form');
});

it('opens the built-in callback form for a plain #form link', function (): void {
    Livewire::test(LeadModal::class)
        ->call('show')
        ->assertSee('Обратный звонок')
        ->assertSeeLivewire('kit-lead-form');
});
