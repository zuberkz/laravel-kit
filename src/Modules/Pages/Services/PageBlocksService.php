<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionParameter;
use TypeError;
use Zuber\Kit\Modules\Pages\Blocks\Block;
use Zuber\Kit\Modules\Pages\Enums\BlockBackgroundEnum;
use Zuber\Kit\Modules\Pages\Enums\BlockSpacingEnum;
use Zuber\Kit\Modules\Pages\Exceptions\BlockException;
use Zuber\Kit\Modules\Pages\Exceptions\PageBuilderException;
use Zuber\Kit\Modules\Pages\Repositories\SharedBlockRepository;

/**
 * Блоки страницы при правке не из админки (MCP): проверка каждого блока до записи — тип есть в
 * проекте, обязательные поля заполнены, лишних полей нет, настройки оформления допустимы, — и
 * операции со списком (вставить, заменить данные, переставить, удалить). Админка проверяет то же
 * формой Filament.
 */
final readonly class PageBlocksService
{
    public function __construct(
        private BlockRegistryService $registry,
        private SharedBlockRepository $sharedBlocks,
    ) {}

    /**
     * Список блоков в том виде, как он хранится: [{type, data}], каждый проверен.
     *
     * @param array<array-key, mixed> $blocks
     *
     * @throws PageBuilderException
     *
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function normalize(array $blocks): array
    {
        return collect(array_values($blocks))
            ->map(fn (mixed $block, int $index): array => $this->block($block, $index))
            ->all();
    }

    /**
     * @param array<array-key, mixed> $blocks
     * @param array<array-key, mixed> $block
     *
     * @throws PageBuilderException
     *
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function insert(array $blocks, array $block, ?int $position = null): array
    {
        $list = array_values($blocks);
        $position ??= count($list);

        if ($position < 0 || $position > count($list)) {
            throw PageBuilderException::indexOutOfRange($position, count($list));
        }

        array_splice($list, $position, 0, [$this->block($block, $position)]);

        return $this->normalize($list);
    }

    /**
     * Данные блока поверх прежних: переданные ключи заменяются, null убирает ключ.
     *
     * @param array<array-key, mixed> $blocks
     * @param array<string, mixed>    $changes
     *
     * @throws PageBuilderException
     *
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function change(array $blocks, int $index, array $changes): array
    {
        $list = $this->normalize($blocks);
        $block = $list[$index] ?? throw PageBuilderException::indexOutOfRange($index, count($list));
        $data = array_filter([...$block['data'], ...$changes], fn (mixed $value): bool => $value !== null);

        $list[$index] = $this->block(['type' => $block['type'], 'data' => $data], $index);

        return $list;
    }

    /**
     * @param array<array-key, mixed> $blocks
     *
     * @throws PageBuilderException
     *
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function move(array $blocks, int $from, int $to): array
    {
        $list = $this->normalize($blocks);

        foreach ([$from, $to] as $index) {
            if (! array_key_exists($index, $list)) {
                throw PageBuilderException::indexOutOfRange($index, count($list));
            }
        }

        $moved = array_splice($list, $from, 1);
        array_splice($list, $to, 0, $moved);

        return $list;
    }

    /**
     * @param array<array-key, mixed> $blocks
     *
     * @throws PageBuilderException
     *
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function remove(array $blocks, int $index): array
    {
        $list = $this->normalize($blocks);

        if (! array_key_exists($index, $list)) {
            throw PageBuilderException::indexOutOfRange($index, count($list));
        }

        array_splice($list, $index, 1);

        return $list;
    }

    /**
     * Данные блока без настроек оформления — с проверкой полей класса; для общего блока.
     *
     * @param array<string, mixed> $data
     *
     * @throws PageBuilderException
     *
     * @return array<string, mixed>
     */
    public function blockData(string $type, array $data): array
    {
        return $this->block(['type' => $type, 'data' => $data], 0)['data'];
    }

    /**
     * @throws PageBuilderException
     *
     * @return array{type: string, data: array<string, mixed>}
     */
    private function block(mixed $block, int $index): array
    {
        $type = is_array($block) && is_string($block['type'] ?? null) ? $block['type'] : '';
        $data = is_array($block) && is_array($block['data'] ?? null) ? $block['data'] : [];
        /** @var array<string, mixed> $data */
        $settings = $this->settings(Arr::only($data, PageRenderService::SETTINGS), $index);

        if ($type === PageRenderService::SHARED_TYPE) {
            $id = is_numeric($data['shared_block_id'] ?? null) ? (int) $data['shared_block_id'] : 0;

            if ($this->sharedBlocks->find($id) === null) {
                throw PageBuilderException::sharedBlockNotFound($id);
            }

            return ['type' => $type, 'data' => ['shared_block_id' => $id, ...$settings]];
        }

        $class = $this->registry->find($type) ?? throw PageBuilderException::unknownType($type);
        $fields = Arr::except($data, PageRenderService::SETTINGS);

        $this->assertBuildable($class, $fields, $index);

        return ['type' => $type, 'data' => [...$fields, ...$settings]];
    }

    /**
     * Блок собирается из данных так же, как при выводе страницы.
     *
     * @param class-string<Block>  $class
     * @param array<string, mixed> $fields
     *
     * @throws PageBuilderException
     */
    private function assertBuildable(string $class, array $fields, int $index): void
    {
        $known = collect((new ReflectionClass($class))->getConstructor()?->getParameters() ?? [])
            ->map(fn (ReflectionParameter $parameter): string => $parameter->getName());
        $unknown = collect(array_keys($fields))->diff($known);

        if ($unknown->isNotEmpty()) {
            throw PageBuilderException::invalidBlock($index, $class::type(), __('нет полей :unknown, есть: :known.', [
                'unknown' => $unknown->implode(', '),
                'known' => $known->implode(', '),
            ]));
        }

        try {
            $class::fromArray($fields);
        } catch (BlockException) {
            throw PageBuilderException::invalidBlock($index, $class::type(), __('не заполнены обязательные поля — list-block-types.'));
        } catch (TypeError) {
            throw PageBuilderException::invalidBlock($index, $class::type(), __('у поля значение не того типа — list-block-types.'));
        }
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @throws PageBuilderException
     *
     * @return array<string, mixed>
     */
    private function settings(array $settings, int $index): array
    {
        $enums = [
            'background' => BlockBackgroundEnum::class,
            'padding_top' => BlockSpacingEnum::class,
            'padding_bottom' => BlockSpacingEnum::class,
        ];

        foreach ($enums as $setting => $enum) {
            $value = $settings[$setting] ?? null;

            if ($value !== null && $value !== '' && (! is_string($value) || $enum::tryFrom($value) === null)) {
                throw PageBuilderException::invalidSetting($index, $setting, collect($enum::cases())->pluck('value')->implode(', '));
            }
        }

        return array_filter([
            ...$settings,
            'hidden' => isset($settings['hidden']) ? (bool) $settings['hidden'] : null,
            'anchor' => is_string($settings['anchor'] ?? null) && $settings['anchor'] !== '' ? $settings['anchor'] : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
