{{-- Страница конструктора в layout проекта (kit.pages.layout): шапка и подвал — слоты header и footer --}}
<x-dynamic-component
    :component="$layout"
    :title="$page->title"
    :description="$page->description"
    :og-image="$page->ogImage"
    :noindex="$page->noindex"
>
    <x-slot:header>
        <x-kit::blocks :blocks="$page->header" />
    </x-slot:header>

    <x-kit::blocks :blocks="$page->main" />

    <x-slot:footer>
        <x-kit::blocks :blocks="$page->footer" />
    </x-slot:footer>
</x-dynamic-component>
