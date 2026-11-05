{{--
    Всплывающее окно с формой заявки (Zuber\Kit\Modules\Leads\Livewire\LeadModal). Ссылки #form и #form-N
    на странице открывают его вместо перехода; закрывается крестиком, Esc и кликом мимо окна. Пока окно
    открыто, страница не прокручивается, а вместо полосы прокрутки у неё отступ той же ширины — вёрстка
    не прыгает вбок, а затемнение закрывает весь экран.
--}}
<div
    x-data="{ open: $wire.entangle('open') }"
    x-on:click.window="
        const link = $event.target.closest('a[href]');
        const match = link ? link.getAttribute('href').match(/^#form(?:-(\d+))?$/) : null;
        if (! match) return;
        $event.preventDefault();
        $wire.show(match[1] ? Number(match[1]) : null, (link.dataset.source || link.textContent).trim());
    "
    x-on:keydown.escape.window="open = false"
    x-effect="
        const html = document.documentElement;
        html.style.paddingRight = open ? (window.innerWidth - html.clientWidth) + 'px' : '';
        html.classList.toggle('overflow-hidden', open);
    "
>
    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
        x-on:click.self="open = false"
        role="dialog"
        aria-modal="true"
        aria-labelledby="kit-lead-modal-title"
    >
        <div
            x-show="open"
            x-transition.scale.95.duration.200ms
            class="relative w-full max-w-lg rounded-card bg-surface p-6 text-ink shadow-card md:p-8"
        >
            <button
                type="button"
                class="absolute top-4 right-4 grid size-10 place-items-center rounded-full text-muted transition-colors duration-200 hover:bg-page hover:text-ink"
                x-on:click="open = false"
                aria-label="{{ __('Закрыть') }}"
            >
                <x-filament::icon icon="heroicon-m-x-mark" class="size-5" />
            </button>

            <h2 id="kit-lead-modal-title" class="pr-10 font-display text-2xl font-semibold">{{ $title }}</h2>

            <div class="mt-6">
                @if ($open)
                    <livewire:kit-lead-form :form="$formId" :source="$source" :key="'kit-lead-modal-' . $opened" />
                @endif
            </div>
        </div>
    </div>
</div>
