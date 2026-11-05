<?php

namespace Zuber\Kit\Tests\Fixtures\Blocks;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Zuber\Kit\Filament\Forms\Fields\BlockImageUpload;
use Zuber\Kit\Pages\Blocks\Block;

final readonly class HeroBlock extends Block
{
    public function __construct(
        public string $title,
        public ?string $text = null,
        public ?string $image = null,
    ) {}

    public static function label(): string
    {
        return 'Первый экран';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('title')->required(),
            Textarea::make('text'),
            BlockImageUpload::make('image'),
        ];
    }

    public static function example(): array
    {
        return ['title' => 'Уборка квартир в Алматы', 'text' => 'За один день'];
    }
}
