{{--
    Блоки страницы по порядку. Props: blocks — array<int, Zuber\Kit\Pages\Data\RenderedBlockData>.
    Обёртка задаёт фон и отступы блока (классы block-bg-*, block-pt-*, block-pb-* — в теме проекта),
    якорь — её id, на него ведут ссылки меню (#prices).
--}}
@props(['blocks'])

@foreach ($blocks as $rendered)
    <div @if ($rendered->anchor) id="{{ $rendered->anchor }}" @endif class="scroll-mt-24 {{ $rendered->classes() }}">
        <x-dynamic-component :component="$rendered->view" :block="$rendered->block" />
    </div>
@endforeach
