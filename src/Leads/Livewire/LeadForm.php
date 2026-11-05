<?php

namespace Zuber\Kit\Leads\Livewire;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Zuber\Kit\Exceptions\DomainException;
use Zuber\Kit\Filament\Forms\Fields\PhoneInput;
use Zuber\Kit\Leads\Actions\SubmitLeadAction;
use Zuber\Kit\Leads\Data\LeadData;
use Zuber\Kit\Spam\HoneypotField;
use Zuber\Kit\Spam\UsesSpamProtection;

/**
 * Форма заявки на сайте: <livewire:kit-lead-form source="Первый экран" button-label="Перезвоните мне" />.
 * Имя, телефон, комментарий; защита от ботов и от повторной отправки, после отправки — «спасибо».
 */
final class LeadForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use UsesSpamProtection;

    #[Locked]
    public string $source = '';

    #[Locked]
    public string $buttonLabel = '';

    /**
     * Страница, на которой открыли форму: в запросе отправки адрес уже другой (Livewire).
     */
    #[Locked]
    public string $url = '';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public bool $sent = false;

    public ?string $error = null;

    public function mount(string $source = '', ?string $buttonLabel = null): void
    {
        $this->source = $source;
        $this->buttonLabel = $buttonLabel ?? __('Отправить заявку');
        $this->url = request()->fullUrl();
        $this->getSchema('form')?->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')
                    ->label(__('Имя'))
                    ->required()
                    ->maxLength(100)
                    ->autocomplete('name'),
                PhoneInput::make()->required(),
                Textarea::make('message')
                    ->label(__('Комментарий'))
                    ->rows(3)
                    ->maxLength(2000),
                HoneypotField::make(),
            ]);
    }

    public function submit(SubmitLeadAction $submit): void
    {
        $state = $this->getSchema('form')?->getState() ?? [];
        $this->error = null;

        // Бот получает то же «спасибо», что и человек: так он не узнаёт, что его поймали
        if ($this->isSpam()) {
            $this->sent = true;

            return;
        }

        try {
            $submit->handle(LeadData::fromForm($state, $this->source, $this->url), (string) request()->ip());
        } catch (DomainException $exception) {
            report($exception);
            $this->error = $exception->getMessage();

            return;
        }

        $this->sent = true;
    }

    public function render(): View
    {
        /** @var view-string $view */
        $view = 'kit::livewire.lead-form';

        return view($view);
    }
}
