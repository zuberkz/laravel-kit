<?php

namespace Zuber\Kit\Leads\Enums;

use Carbon\CarbonImmutable;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Throwable;
use Zuber\Kit\Filament\Forms\Fields\PhoneInput;
use Zuber\Kit\Leads\Data\LeadFieldData;
use Zuber\Kit\Support\PhoneSupport;

/**
 * Тип поля формы заявки: какой компонент Filament его рисует и как показать ответ в заявке.
 */
enum LeadFieldTypeEnum: string implements HasLabel
{
    case NAME = 'name';
    case PHONE = 'phone';
    case EMAIL = 'email';
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';
    case DATE = 'date';

    public function getLabel(): string
    {
        return match ($this) {
            self::NAME => __('Имя'),
            self::PHONE => __('Телефон'),
            self::EMAIL => __('Email'),
            self::TEXT => __('Строка'),
            self::TEXTAREA => __('Текст'),
            self::SELECT => __('Выбор из вариантов'),
            self::CHECKBOX => __('Флажок'),
            self::DATE => __('Дата'),
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::NAME => Heroicon::OutlinedUser,
            self::PHONE => Heroicon::OutlinedPhone,
            self::EMAIL => Heroicon::OutlinedEnvelope,
            self::TEXT => Heroicon::OutlinedPencil,
            self::TEXTAREA => Heroicon::OutlinedBars3BottomLeft,
            self::SELECT => Heroicon::OutlinedListBullet,
            self::CHECKBOX => Heroicon::OutlinedCheckCircle,
            self::DATE => Heroicon::OutlinedCalendarDays,
        };
    }

    /**
     * По этому полю с клиентом можно связаться: в форме должно быть хотя бы одно такое.
     */
    public function isContact(): bool
    {
        return match ($this) {
            self::PHONE, self::EMAIL => true,
            self::NAME, self::TEXT, self::TEXTAREA, self::SELECT, self::CHECKBOX, self::DATE => false,
        };
    }

    /**
     * Компонент Filament для поля на сайте; подпись, подсказка и «обязательное» — общие.
     */
    public function component(LeadFieldData $field): Field
    {
        $component = match ($this) {
            self::NAME => TextInput::make($field->key)->autocomplete('name')->maxLength(100),
            self::PHONE => PhoneInput::make($field->key),
            self::EMAIL => TextInput::make($field->key)->email()->autocomplete('email')->maxLength(255),
            self::TEXT => TextInput::make($field->key)->maxLength(255),
            self::TEXTAREA => Textarea::make($field->key)->rows(3)->maxLength(2000),
            self::SELECT => Select::make($field->key)->options(array_combine($field->options, $field->options))->native(false),
            self::CHECKBOX => Checkbox::make($field->key)->accepted($field->required),
            self::DATE => DatePicker::make($field->key)->native(false)->displayFormat('d.m.Y')->minDate(today()),
        };

        $component
            ->label($field->label)
            ->required($field->required && $this !== self::CHECKBOX)
            ->helperText($field->hint)
            ->columnSpan($field->half ? 1 : 'full');

        if ($field->placeholder !== null && method_exists($component, 'placeholder')) {
            $component->placeholder($field->placeholder);
        }

        return $component;
    }

    /**
     * Ответ для заявки и письма: строкой, как его прочтёт менеджер; пустой ответ — null.
     */
    public function display(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return $this === self::CHECKBOX ? self::yesNo(false) : null;
        }

        return match ($this) {
            self::CHECKBOX => self::yesNo((bool) $value),
            self::PHONE => PhoneSupport::format(is_string($value) ? $value : null),
            self::DATE => self::date($value),
            self::NAME, self::EMAIL, self::TEXT, self::TEXTAREA, self::SELECT => is_scalar($value) ? trim((string) $value) : null,
        };
    }

    private static function yesNo(bool $value): string
    {
        return $value ? __('Да') : __('Нет');
    }

    private static function date(mixed $value): ?string
    {
        try {
            return is_string($value) ? CarbonImmutable::parse($value)->format('d.m.Y') : null;
        } catch (Throwable) {
            return null;
        }
    }
}
