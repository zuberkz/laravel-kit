<?php

namespace Zuber\Kit\Pages\Filament\Resources\Pages\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Zuber\Kit\Filament\Forms\Fields\BlockImageUpload;
use Zuber\Kit\Pages\Data\PageData;
use Zuber\Kit\Pages\Enums\PageStatusEnum;
use Zuber\Kit\Pages\Filament\PageBuilder;
use Zuber\Kit\Pages\Support\PagePathSupport;

/**
 * Страница вкладками: блоки конструктора, настройки страницы и SEO.
 */
final class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make()
                    ->tabs([
                        Tab::make(__('Блоки'))
                            ->icon(Heroicon::OutlinedSquares2x2)
                            ->schema([
                                Text::make(__('Перетаскивайте, чтобы поменять порядок. Повторяющийся на многих страницах раздел — «Общий блок».'))
                                    ->color('gray'),
                                PageBuilder::make(),
                            ]),
                        Tab::make(__('Страница'))
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema(self::page()),
                        Tab::make(__('SEO'))
                            ->icon(Heroicon::OutlinedMagnifyingGlass)
                            ->schema(self::seo()),
                    ])
                    // Новую страницу начинают с названия и адреса, существующую — с блоков
                    ->activeTab(fn (string $operation): int => $operation === 'create' ? 2 : 1)
                    ->persistTabInQueryString(),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    private static function page(): array
    {
        return [
            Grid::make(['md' => 2])->schema([
                TextInput::make('title')
                    ->label(__('Название'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create' && blank($get('path'))) {
                            $set('path', '/' . Str::slug((string) $state));
                        }
                    }),
                TextInput::make('path')
                    ->label(__('Адрес'))
                    ->required()
                    ->maxLength(255)
                    ->prefix(rtrim(url('/'), '/'))
                    ->regex(PagePathSupport::PATTERN)
                    ->unique(ignoreRecord: true)
                    ->rule(fn (Router $router): Closure => function (string $attribute, mixed $value, Closure $fail) use ($router): void {
                        if (is_string($value) && PagePathSupport::isTakenByRoute($router, PageData::normalizePath($value))) {
                            $fail(__('Этот адрес занят разделом сайта.'));
                        }
                    })
                    ->validationMessages(['regex' => __('Латиница в нижнем регистре, цифры и дефисы: /services/cleaning. Главная — /')])
                    ->helperText(__('Главная страница — /')),
                Select::make('status')
                    ->label(__('Статус'))
                    ->options(PageStatusEnum::class)
                    ->default(PageStatusEnum::DRAFT)
                    ->selectablePlaceholder(false)
                    ->native(false)
                    ->required(),
            ]),
            Fieldset::make(__('Общие блоки сайта на этой странице'))
                ->columns(['sm' => 2])
                ->schema([
                    Toggle::make('show_header')
                        ->label(__('Шапка'))
                        ->default(true),
                    Toggle::make('show_footer')
                        ->label(__('Подвал'))
                        ->default(true),
                ]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function seo(): array
    {
        return [
            Text::make(__('Пусто — заголовок страницы берётся из названия.'))->color('gray'),
            Grid::make(['md' => 2])->schema([
                TextInput::make('seo_title')
                    ->label(__('Title'))
                    ->maxLength(70)
                    ->helperText(__('До 60 символов, главный запрос — в начале')),
                Textarea::make('seo_description')
                    ->label(__('Description'))
                    ->rows(3)
                    ->maxLength(160)
                    ->helperText(__('140–160 символов: выгода и призыв')),
                BlockImageUpload::make('og_image')
                    ->label(__('Картинка для соцсетей'))
                    ->helperText(__('1200×630')),
                Toggle::make('noindex')
                    ->label(__('Закрыть от поисковиков')),
            ]),
        ];
    }
}
