{{-- Картинка блока (класс Zuber\Kit\Modules\Pages\View\Components\Image): src, alt, eager — первый экран --}}
<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    decoding="async"
    @if ($eager) fetchpriority="high" @endif
    {{ $attributes }}
>
