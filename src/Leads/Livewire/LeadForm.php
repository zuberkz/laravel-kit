<?php

namespace Zuber\Kit\Leads\Livewire;

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Zuber\Kit\Exceptions\DomainException;
use Zuber\Kit\Leads\Actions\SubmitLeadAction;
use Zuber\Kit\Leads\Data\LeadFormDefinitionData;
use Zuber\Kit\Leads\Services\LeadFormService;
use Zuber\Kit\Spam\HoneypotField;
use Zuber\Kit\Spam\UsesSpamProtection;

/**
 * Форма заявки на сайте: <livewire:kit-lead-form :form="$block->form" source="Первый экран" />.
 * Поля — из формы, собранной в админке (без неё — «Обратный звонок»); защита от ботов и от
 * повторной отправки, после отправки — «спасибо» формы.
 */
final class LeadForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use UsesSpamProtection;

    #[Locked]
    public ?int $formId = null;

    #[Locked]
    public string $source = '';

    /**
     * Текст кнопки из блока; пусто — текст формы. Livewire кладёт сюда параметр до mount(), null тоже.
     */
    #[Locked]
    public ?string $buttonLabel = null;

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

    public function mount(?int $form = null, string $source = '', ?string $buttonLabel = null): void
    {
        $this->formId = $form;
        $this->source = $source;
        $this->buttonLabel = $buttonLabel;
        $this->url = request()->fullUrl();
        $this->getSchema('form')?->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(['sm' => 2])
            ->components([
                ...app(LeadFormService::class)->components($this->definition()),
                HoneypotField::make(),
            ]);
    }

    public function submit(SubmitLeadAction $submit, LeadFormService $forms): void
    {
        $state = $this->getSchema('form')?->getState() ?? [];
        $this->error = null;

        // Бот получает то же «спасибо», что и человек: так он не узнаёт, что его поймали
        if ($this->isSpam()) {
            $this->sent = true;

            return;
        }

        $definition = $this->definition();

        try {
            $submit->handle($forms->lead($definition, $state, $this->source, $this->url), (string) request()->ip(), $definition->mailTo);
        } catch (DomainException $exception) {
            report($exception);
            $this->error = $exception->getMessage();

            return;
        }

        $this->sent = true;
    }

    private function definition(): LeadFormDefinitionData
    {
        return once(fn (): LeadFormDefinitionData => app(LeadFormService::class)->definition($this->formId));
    }

    public function render(): View
    {
        /** @var view-string $view */
        $view = 'kit::livewire.lead-form';
        $definition = $this->definition();

        return view($view, [
            'button' => $this->buttonLabel !== null && $this->buttonLabel !== '' ? $this->buttonLabel : $definition->buttonLabel,
            'successMessage' => $definition->successMessage,
        ]);
    }
}
