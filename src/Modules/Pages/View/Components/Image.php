<?php

namespace Zuber\Kit\Modules\Pages\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Zuber\Kit\Modules\Pages\Services\BlockImageService;

/**
 * Картинка блока: <x-kit::image :path="$block->image" :alt="$block->imageAlt" eager />.
 * Ниже первого экрана — ленивая загрузка; первый экран — eager и высокий приоритет.
 */
final class Image extends Component
{
    public ?string $src;

    public function __construct(
        BlockImageService $images,
        public ?string $path = null,
        public string $alt = '',
        public bool $eager = false,
    ) {
        $this->src = $images->url($path);
    }

    public function shouldRender(): bool
    {
        return $this->src !== null;
    }

    public function render(): View
    {
        /** @var view-string $view */
        $view = 'kit::components.image';

        return view($view);
    }
}
