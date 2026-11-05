{{--
    Блоки страницы по порядку. Props: blocks — array<int, Zuber\Kit\Pages\Data\RenderedBlockData>.
    Якорь блока — id обёртки, на него ведут ссылки меню (#prices).
--}}
@props(['blocks'])

@foreach ($blocks as $rendered)
    <div @if ($rendered->anchor) id="{{ $rendered->anchor }}" @endif class="scroll-mt-24">
        <x-dynamic-component :component="$rendered->view" :block="$rendered->block" />
    </div>
@endforeach
