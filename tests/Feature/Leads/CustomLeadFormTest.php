<?php

use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\Pages\CreateLeadForm;
use Zuber\Kit\Leads\Livewire\LeadForm as LeadFormComponent;
use Zuber\Kit\Leads\Models\Lead;
use Zuber\Kit\Leads\Models\LeadForm;
use Zuber\Kit\Leads\Notifications\NewLeadNotification;
use Zuber\Kit\Tests\Fixtures\RoleEnum;

beforeEach(function (): void {
    Notification::fake();
});

function cleaningForm(): LeadForm
{
    return LeadForm::query()->create([
        'name' => 'Расчёт уборки',
        'button_label' => 'Рассчитать',
        'success_message' => 'Пришлём расчёт в WhatsApp.',
        'mail_to' => 'cleaning@example.kz, boss@example.kz',
        'fields' => [
            ['type' => 'phone', 'data' => ['key' => 'phone', 'label' => 'Телефон', 'required' => true]],
            ['type' => 'select', 'data' => ['key' => 'kind', 'label' => 'Тип уборки', 'required' => true, 'options' => ['Поддерживающая', 'Генеральная']]],
            ['type' => 'checkbox', 'data' => ['key' => 'sofa', 'label' => 'Химчистка дивана']],
            ['type' => 'email', 'data' => ['key' => 'email', 'label' => 'Почта']],
        ],
    ]);
}

it('builds the site form from the fields made in the admin and stores the answers', function (): void {
    $form = cleaningForm();

    $component = Livewire::test(LeadFormComponent::class, ['form' => $form->id, 'source' => 'Цены'])
        ->assertSee(['Тип уборки', 'Химчистка дивана', 'Рассчитать'])
        ->assertDontSee('Комментарий')
        ->fillForm(['phone' => '+7 (707) 112-25-36', 'kind' => 'Генеральная', 'sofa' => true]);
    $this->travel(5)->seconds();
    $component->call('submit')->assertHasNoFormErrors()->assertSee('Пришлём расчёт в WhatsApp.');

    $lead = Lead::query()->sole();
    expect($lead->lead_form_id)->toBe($form->id)
        ->and($lead->phone)->toBe('+77071122536')
        ->and($lead->name)->toBeNull()
        ->and($lead->answers)->toBe([
            ['label' => 'Телефон', 'value' => '+7 (707) 112-25-36'],
            ['label' => 'Тип уборки', 'value' => 'Генеральная'],
            ['label' => 'Химчистка дивана', 'value' => 'Да'],
        ]);

    Notification::assertSentOnDemand(NewLeadNotification::class, fn (NewLeadNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === ['cleaning@example.kz', 'boss@example.kz']);
});

it('takes the button text of the form unless the block sets its own', function (): void {
    $form = cleaningForm();

    Livewire::test(LeadFormComponent::class, ['form' => $form->id, 'buttonLabel' => null])->assertSee('Рассчитать');
    Livewire::test(LeadFormComponent::class, ['form' => $form->id, 'buttonLabel' => 'Хочу скидку'])->assertSee('Хочу скидку')->assertDontSee('Рассчитать');
});

it('validates the fields of a custom form', function (): void {
    Livewire::test(LeadFormComponent::class, ['form' => cleaningForm()->id])
        ->fillForm(['phone' => '', 'kind' => 'Другое', 'email' => 'не почта'])
        ->call('submit')
        ->assertHasFormErrors(['phone' => 'required', 'kind' => 'in', 'email' => 'email']);
});

it('falls back to the callback form when the chosen form was deleted', function (): void {
    $form = cleaningForm();
    $form->delete();

    Livewire::test(LeadFormComponent::class, ['form' => $form->id])->assertSee(['Имя', 'Комментарий']);
});

it('builds a form in the admin and refuses one without a phone or an email', function (): void {
    $this->seedRoles();
    Filament::setCurrentPanel('panel');
    $this->actingAs($this->userWithRole(RoleEnum::MANAGER));
    $undoFake = Builder::fake();

    Livewire::test(CreateLeadForm::class)
        ->fillForm(['name' => 'Без контактов', 'fields' => [['type' => 'name', 'data' => ['label' => 'Имя']]]])
        ->call('create')
        ->assertNotified('Добавьте в форму поле «Телефон» или «Email»: иначе с клиентом не связаться.');

    Livewire::test(CreateLeadForm::class)
        ->fillForm(['name' => 'Опт', 'fields' => [
            ['type' => 'name', 'data' => ['label' => 'Ваше имя', 'required' => true]],
            ['type' => 'phone', 'data' => ['label' => 'Телефон', 'required' => true]],
            ['type' => 'text', 'data' => ['label' => 'Ваше имя']],
        ]])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoFake();

    expect(LeadForm::query()->count())->toBe(1)
        ->and(collect(LeadForm::query()->sole()->fields)->pluck('data.key')->all())->toBe(['vase_imia', 'telefon', 'vase_imia_2']);
});
