<section class="hero">
    <h1>{{ $block->title }}</h1>
    @if ($block->text)
        <p>{{ $block->text }}</p>
    @endif
    <x-kit::image :path="$block->image" alt="Обложка" eager />
</section>
