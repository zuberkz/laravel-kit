<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Zuber\Kit\Modules\Pages\Blocks\Block;
use Zuber\Kit\Modules\Pages\Enums\BlockBackgroundEnum;
use Zuber\Kit\Modules\Pages\Enums\BlockSpacingEnum;

/**
 * Описание блоков проекта для правки страниц не из админки (MCP): поля блока из его конструктора
 * (имя, тип, обязательность), подписи и допустимые значения — из полей Filament, пример данных —
 * example(): по нему видно и формат вложенных списков, и приёмы вроде *акцента* в заголовке.
 */
final readonly class BlockSchemaService
{
    public function __construct(private BlockRegistryService $registry) {}

    /**
     * @return array<int, array{type: string, label: string, fields: array<int, array<string, mixed>>, example: array<string, mixed>, background: string, spacing: string}>
     */
    public function types(): array
    {
        return collect($this->registry->all())
            ->map(fn (string $class): array => [
                'type' => $class::type(),
                'label' => $class::label(),
                'fields' => $this->fields($class),
                'example' => $class::example(),
                'background' => $class::background()->value,
                'spacing' => $class::spacing()->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Настройки «Оформления», которые кит добавляет к любому блоку.
     *
     * @return array<string, array<string, mixed>>
     */
    public function settings(): array
    {
        return [
            'anchor' => ['type' => 'string', 'hint' => __('Якорь для ссылок: services → ссылка #services')],
            'hidden' => ['type' => 'bool', 'hint' => __('true — блок не показывается на сайте')],
            'background' => ['type' => 'string', 'options' => $this->options(BlockBackgroundEnum::cases()), 'hint' => __('Пусто — как задано в блоке')],
            'padding_top' => ['type' => 'string', 'options' => $this->options(BlockSpacingEnum::cases()), 'hint' => __('Пусто — как задано в блоке')],
            'padding_bottom' => ['type' => 'string', 'options' => $this->options(BlockSpacingEnum::cases()), 'hint' => __('Пусто — как задано в блоке')],
        ];
    }

    /**
     * @param  class-string<Block>              $class
     * @return array<int, array<string, mixed>>
     */
    private function fields(string $class): array
    {
        $formFields = collect($class::fields())
            ->filter(fn (mixed $field): bool => $field instanceof Field)
            ->keyBy(fn (Field $field): string => $field->getName());

        return collect((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [])
            ->map(function (ReflectionParameter $parameter) use ($formFields): array {
                $field = $formFields->get($parameter->getName());
                $type = $parameter->getType();

                return array_filter([
                    'name' => $parameter->getName(),
                    'type' => $type instanceof ReflectionNamedType ? $type->getName() : 'mixed',
                    'required' => ! $parameter->isDefaultValueAvailable() && ! $parameter->allowsNull(),
                    'label' => $field instanceof Field ? $this->text(fn (): mixed => $field->getLabel()) : null,
                    'options' => $field instanceof Field ? $this->fieldOptions($field) : null,
                ], fn (mixed $value): bool => $value !== null && $value !== []);
            })
            ->values()
            ->all();
    }

    /**
     * Допустимые значения поля выбора (иконка, форма заявки): значение → подпись.
     *
     * @return array<string, string>|null
     */
    private function fieldOptions(Field $field): ?array
    {
        if (! method_exists($field, 'getOptions')) {
            return null;
        }

        $options = rescue(fn (): mixed => $field->getOptions(), null, report: false);

        return is_array($options)
            ? collect($options)->map(fn (mixed $label): string => $label instanceof Htmlable ? strip_tags($label->toHtml()) : (is_scalar($label) ? (string) $label : ''))->all()
            : null;
    }

    /**
     * Подпись поля, если её можно вычислить без формы (замыкание с формой — пропускаем).
     *
     * @param callable(): mixed $value
     */
    private function text(callable $value): ?string
    {
        $text = rescue($value, null, report: false);

        if ($text instanceof Htmlable) {
            $text = strip_tags($text->toHtml());
        }

        return is_string($text) && $text !== '' ? $text : null;
    }

    /**
     * @param  array<int, BlockBackgroundEnum|BlockSpacingEnum> $cases
     * @return array<string, string>
     */
    private function options(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn (BlockBackgroundEnum|BlockSpacingEnum $case): array => [$case->value => (string) $case->getLabel()])->all();
    }
}
