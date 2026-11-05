<?php

namespace Zuber\Kit\Modules\Leads\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Zuber\Kit\Modules\Leads\Services\LeadFormService;

/**
 * Форма заявки во всплывающем окне: <livewire:kit-lead-modal /> один раз в layout сайта.
 * Окно открывает любая ссылка #form (встроенная «Обратный звонок») или #form-3 (форма № 3)
 * — их можно вписать в кнопку любого блока — или событие kit-open-lead-form.
 */
final class LeadModal extends Component
{
    public bool $open = false;

    public ?int $formId = null;

    public string $source = '';

    /**
     * Сколько раз окно открывали: ключ формы внутри, чтобы она каждый раз была чистой.
     */
    public int $opened = 0;

    #[On('kit-open-lead-form')]
    public function show(?int $form = null, string $source = ''): void
    {
        $this->formId = $form;
        $this->source = $source;
        $this->opened++;
        $this->open = true;
    }

    public function render(LeadFormService $forms): View
    {
        /** @var view-string $view */
        $view = 'kit::livewire.lead-modal';

        return view($view, ['title' => $forms->definition($this->formId)->name]);
    }
}
