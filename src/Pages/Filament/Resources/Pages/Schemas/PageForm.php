<?php

namespace Zuber\Kit\Pages\Filament\Resources\Pages\Schemas;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
 * Страница: основные настройки, блоки конструктора и SEO — одним экраном.
 */
final class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::page(),
                Section::make(__('Блоки'))
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->description(__('Перетаскивайте, чтобы поменять порядок. Повторяющийся на многих страницах раздел — «Общий блок».'))
                    ->schema([PageBuilder::make()]),
                self::seo(),
            ]);
    }

    private static function page(): Section
    {
        return Section::make(__('Страница'))
            ->icon(Heroicon::OutlinedDocumentText)
            ->columns(['md' => 2])
            ->schema([
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
                    ->native(false)
                    ->required(),
                Toggle::make('show_header')
                    ->label(__('Шапка сайта'))
                    ->default(true)
                    ->inline(false),
                Toggle::make('show_footer')
                    ->label(__('Подвал сайта'))
                    ->default(true)
                    ->inline(false),
            ]);
    }

    private static function seo(): Section
    {
        return Section::make(__('SEO'))
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->description(__('Пусто — заголовок страницы берётся из названия'))
            ->collapsible()
            ->collapsed()
            ->columns(['md' => 2])
            ->schema([
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
                    ->label(__('Закрыть от поисковиков'))
                    ->inline(false),
            ]);
    }
}
