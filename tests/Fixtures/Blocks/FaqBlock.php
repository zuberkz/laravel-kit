<?php

namespace Zuber\Kit\Tests\Fixtures\Blocks;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Zuber\Kit\Pages\Blocks\Block;

final readonly class FaqBlock extends Block
{
    /**
     * @param array<int, array{question: string, answer: string}> $items
     */
    public function __construct(
        public string $title = 'Вопросы и ответы',
        public array $items = [],
    ) {}

    public static function label(): string
    {
        return 'Вопросы и ответы';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('title'),
            Repeater::make('items')->schema([TextInput::make('question'), TextInput::make('answer')]),
        ];
    }

    public static function example(): array
    {
        return ['items' => [['question' => 'Сколько стоит?', 'answer' => 'От 15 000 ₸']]];
    }
}
