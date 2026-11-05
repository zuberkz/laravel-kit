<?php

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Zuber\Kit\Leads\Enums\LeadStatusEnum;
use Zuber\Kit\Leads\Filament\Resources\Leads\LeadResource;
use Zuber\Kit\Leads\Filament\Resources\Leads\Pages\ListLeads;
use Zuber\Kit\Leads\Livewire\LeadForm;
use Zuber\Kit\Leads\Models\Lead;
use Zuber\Kit\Leads\Notifications\NewLeadNotification;
use Zuber\Kit\Tests\Fixtures\RoleEnum;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @param array<string, mixed> $data
 */
function submitLead(array $data = []): Testable
{
    $form = Livewire::test(LeadForm::class, ['source' => 'Первый экран'])
        ->fillForm(['name' => 'Айгерим', 'phone' => '+7 (707) 112-25-36', 'message' => 'Перезвоните после 18:00', ...$data]);
    test()->travel(5)->seconds();

    return $form->call('submit');
}

it('stores the lead, thanks the visitor and mails the team', function (): void {
    submitLead()->assertHasNoFormErrors()->assertSet('sent', true)->assertSee('Спасибо! Заявка отправлена.');

    $lead = Lead::query()->sole();
    expect($lead->only(['name', 'phone', 'message', 'source']))->toBe([
        'name' => 'Айгерим', 'phone' => '+77071122536', 'message' => 'Перезвоните после 18:00', 'source' => 'Первый экран',
    ])->and($lead->status)->toBe(LeadStatusEnum::NEW);

    Notification::assertSentOnDemand(NewLeadNotification::class, fn (NewLeadNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === ['team@example.kz']);
});

it('asks for a name and the whole phone number', function (): void {
    submitLead(['name' => '', 'phone' => '+7 (707) 11'])->assertHasFormErrors(['name' => 'required', 'phone' => 'regex']);

    expect(Lead::query()->count())->toBe(0);
});

it('thanks a bot without storing anything', function (): void {
    submitLead(['website' => 'http://spam.example'])->assertSet('sent', true);

    expect(Lead::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('stops a flood of leads from one address', function (): void {
    config(['kit.leads.per_minute' => 2]);
    submitLead();
    submitLead();

    submitLead()->assertSet('sent', false)->assertSee('Заявка уже отправлена');

    expect(Lead::query()->count())->toBe(2);
});

it('lists leads in the admin, counts new ones and marks a lead processed', function (): void {
    $this->seedRoles();
    Filament::setCurrentPanel('panel');
    $this->actingAs($this->userWithRole(RoleEnum::MANAGER));
    $lead = Lead::query()->create(['name' => 'Айгерим', 'phone' => '+77071122536', 'status' => LeadStatusEnum::NEW]);

    expect(LeadResource::getNavigationBadge())->toBe('1');

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$lead])
        ->assertSee('+7 (707) 112-25-36')
        ->callAction(TestAction::make('markProcessed')->table($lead));

    expect($lead->refresh()->status)->toBe(LeadStatusEnum::PROCESSED)
        ->and(LeadResource::getNavigationBadge())->toBeNull()
        ->and(LeadResource::canCreate())->toBeFalse();
});
