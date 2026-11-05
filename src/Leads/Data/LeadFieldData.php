<?php

namespace Zuber\Kit\Leads\Data;

use Illuminate\Support\Arr;
use Zuber\Kit\Leads\Enums\LeadFieldTypeEnum;

/**
 * Поле формы заявки: что спросить и как. key — ключ ответа, уникальный в форме.
 */
final readonly class LeadFieldData
{
    /**
     * @param array<int, string> $options варианты для «Выбора»
     */
    public function __construct(
        public LeadFieldTypeEnum $type,
        public string $key,
        public string $label,
        public bool $required = false,
        public ?string $placeholder = null,
        public ?string $hint = null,
        public array $options = [],
        public bool $half = false,
    ) {}

    /**
     * Поле из карточки конструктора: {type, data}. Неизвестный тип (из старой версии) — null.
     *
     * @param array<array-key, mixed> $item
     */
    public static function fromBuilderItem(array $item): ?self
    {
        $type = LeadFieldTypeEnum::tryFrom(is_string($item['type'] ?? null) ? $item['type'] : '');
        $data = is_array($item['data'] ?? null) ? $item['data'] : [];
        $key = Arr::get($data, 'key');
        $label = Arr::get($data, 'label');

        if ($type === null || ! is_string($key) || $key === '' || ! is_string($label) || $label === '') {
            return null;
        }

        return new self(
            type: $type,
            key: $key,
            label: $label,
            required: (bool) Arr::get($data, 'required', false),
            placeholder: self::text(Arr::get($data, 'placeholder')),
            hint: self::text(Arr::get($data, 'hint')),
            options: collect(Arr::wrap(Arr::get($data, 'options', [])))
                ->filter(fn (mixed $option): bool => is_string($option) && trim($option) !== '')
                ->map(fn (string $option): string => trim($option))
                ->values()
                ->all(),
            half: (bool) Arr::get($data, 'half', false),
        );
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public function toBuilderItem(): array
    {
        return ['type' => $this->type->value, 'data' => [
            'key' => $this->key,
            'label' => $this->label,
            'required' => $this->required,
            'placeholder' => $this->placeholder,
            'hint' => $this->hint,
            'options' => $this->options,
            'half' => $this->half,
        ]];
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
