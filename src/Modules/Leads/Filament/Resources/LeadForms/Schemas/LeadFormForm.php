<?php

namespace Zuber\Kit\Modules\Leads\Filament\Resources\LeadForms\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Zuber\Kit\Modules\Leads\Enums\LeadFieldTypeEnum;

/**
 * Форма заявки вкладками: поля (конструктор) и тексты с адресами для писем.
 */
final class LeadFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()->schema([
                    TextInput::make('name')
                        ->label(__('Название'))
                        ->helperText(__('Видно в админке и в заявках: «Расчёт уборки»'))
                        ->required()
                        ->maxLength(255),
                ]),
                Tabs::make()
                    ->tabs([
                        Tab::make(__('Поля'))
                            ->icon(Heroicon::OutlinedQueueList)
                            ->schema([self::fields()]),
                        Tab::make(__('Тексты и письма'))
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema([
                                Grid::make(['md' => 2])->schema([
                                    TextInput::make('button_label')
                                        ->label(__('Кнопка'))
                                        ->placeholder(__('Отправить заявку'))
                                        ->maxLength(60),
                                    TextInput::make('mail_to')
                                        ->label(__('Кому письмо'))
                                        ->placeholder(__('Общие адреса из настроек сайта'))
                                        ->helperText(__('Несколько адресов — через запятую'))
                                        ->maxLength(255),
                                    Textarea::make('success_message')
                                        ->label(__('Текст после отправки'))
                                        ->placeholder(__('Мы свяжемся с вами в ближайшее рабочее время.'))
                                        ->rows(2)
                                        ->maxLength(500)
                                        ->columnSpanFull(),
                                ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ]);
    }

    private static function fields(): Builder
    {
        return Builder::make('fields')
            ->hiddenLabel()
            ->blocks(collect(LeadFieldTypeEnum::cases())->map(fn (LeadFieldTypeEnum $type): Block => self::field($type))->all())
            ->default([
                ['type' => LeadFieldTypeEnum::NAME->value, 'data' => ['label' => __('Имя'), 'required' => true, 'half' => true]],
                ['type' => LeadFieldTypeEnum::PHONE->value, 'data' => ['label' => __('Телефон'), 'required' => true, 'half' => true]],
            ])
            ->required()
            ->blockIcons()
            ->blockNumbers(false)
            ->blockPickerColumns(['md' => 2])
            ->addActionLabel(__('Добавить поле'))
            ->collapsible()
            ->collapsed(fn (string $operation): bool => $operation === 'edit')
            ->cloneable();
    }

    private static function field(LeadFieldTypeEnum $type): Block
    {
        return Block::make($type->value)
            ->label(fn (?array $state): string => self::itemLabel($type, $state))
            ->icon($type->icon())
            ->columns(['md' => 2])
            ->schema([
                Hidden::make('key'),
                TextInput::make('label')
                    ->label(__('Подпись'))
                    ->default($type->getLabel())
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true),
                TextInput::make('placeholder')
                    ->label(__('Подсказка в поле'))
                    ->maxLength(120)
                    ->visible($type !== LeadFieldTypeEnum::CHECKBOX),
                TagsInput::make('options')
                    ->label(__('Варианты'))
                    ->placeholder(__('Введите вариант и нажмите Enter'))
                    ->required()
                    ->columnSpanFull()
                    ->visible($type === LeadFieldTypeEnum::SELECT),
                TextInput::make('hint')
                    ->label(__('Пояснение под полем'))
                    ->maxLength(200)
                    ->columnSpanFull(),
                Toggle::make('required')
                    ->label($type === LeadFieldTypeEnum::CHECKBOX ? __('Обязательно отметить') : __('Обязательное')),
                Toggle::make('half')
                    ->label(__('В половину ширины')),
            ]);
    }

    /**
     * @param array<string, mixed>|null $state
     */
    private static function itemLabel(LeadFieldTypeEnum $type, ?array $state): string
    {
        $label = Arr::get($state ?? [], 'label');
        $text = is_string($label) && $label !== '' && $label !== $type->getLabel() ? $type->getLabel() . ' — ' . $label : $type->getLabel();

        return Arr::get($state ?? [], 'required') === true ? $text . ' *' : $text;
    }
}
