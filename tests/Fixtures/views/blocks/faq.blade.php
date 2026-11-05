<section class="faq">
    <h2>{{ $block->title }}</h2>
    @foreach ($block->items as $item)
        <p>{{ $item['question'] }} — {{ $item['answer'] }}</p>
    @endforeach
</section>
