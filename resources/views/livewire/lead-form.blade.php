{{-- Форма заявки (Zuber\Kit\Modules\Leads\Livewire\LeadForm): поля Filament из формы админки, кнопка и «спасибо» — на токенах темы проекта --}}
<div>
    @if ($sent)
        <div class="rounded-card bg-surface p-6 text-ink shadow-card" role="status">
            <p class="text-lg font-semibold">{{ __('Спасибо! Заявка отправлена.') }}</p>
            <p class="mt-1 text-muted">{{ $successMessage }}</p>
        </div>
    @else
        <form wire:submit="submit" class="grid gap-4">
            {{ $this->form }}

            @if ($error)
                <p class="text-sm text-danger" role="alert">{{ $error }}</p>
            @endif

            <button
                type="submit"
                class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand px-6 py-3 font-medium text-on-brand transition-colors duration-200 hover:bg-brand-hover disabled:opacity-60"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="submit">{{ $button }}</span>
                <span wire:loading wire:target="submit">{{ __('Отправляем…') }}</span>
            </button>
        </form>
    @endif
</div>
