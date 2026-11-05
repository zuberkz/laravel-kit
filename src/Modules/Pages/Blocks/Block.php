<?php

namespace Zuber\Kit\Modules\Pages\Blocks;

use BackedEnum;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionParameter;
use Zuber\Kit\Modules\Pages\Enums\BlockBackgroundEnum;
use Zuber\Kit\Modules\Pages\Enums\BlockSpacingEnum;
use Zuber\Kit\Modules\Pages\Exceptions\BlockException;

/**
 * Блок конструктора страниц: один класс в app/Blocks и один шаблон resources/views/blocks/{type}.
 * Свойства конструктора — данные блока (шаблон получает готовый объект), fields() — поля в админке
 * с теми же именами, example() — пример для заготовки страницы и для теста «блок отрисовывается».
 *
 *     final readonly class HeroBlock extends Block
 *     {
 *         public function __construct(public string $title, public ?string $text = null) {}
 *
 *         public static function label(): string { return __('Первый экран'); }
 *
 *         public static function fields(): array
 *         {
 *             return [TextInput::make('title')->required(), Textarea::make('text')];
 *         }
 *     }
 *
 * Поля «Оформления» (anchor, hidden, background, padding_top, padding_bottom) кит добавляет к каждому
 * блоку сам — свойства с такими именами заняты. Фон и отступы по умолчанию — background() и spacing().
 */
abstract readonly class Block
{
    abstract public static function label(): string;

    /**
     * @return array<int, Component|\Filament\Forms\Components\Field>
     */
    abstract public static function fields(): array;

    /**
     * Тип в данных страницы и имя шаблона: HeroBlock → hero, TextImageBlock → text-image.
     */
    public static function type(): string
    {
        return Str::kebab(Str::beforeLast(class_basename(static::class), 'Block'));
    }

    public static function icon(): string|BackedEnum
    {
        return Heroicon::OutlinedSquare3Stack3d;
    }

    /**
     * Пример данных: заготовка новой страницы и тест, что шаблон блока отрисовывается.
     *
     * @return array<string, mixed>
     */
    public static function example(): array
    {
        return [];
    }

    /**
     * Фон по умолчанию: в админке поле пустое — «как у блока».
     */
    public static function background(): BlockBackgroundEnum
    {
        return BlockBackgroundEnum::PAGE;
    }

    /**
     * Отступы сверху и снизу по умолчанию.
     */
    public static function spacing(): BlockSpacingEnum
    {
        return BlockSpacingEnum::MEDIUM;
    }

    public static function view(): string
    {
        return 'blocks::' . static::type();
    }

    /**
     * Блок из данных страницы: лишние ключи (anchor, hidden, старые поля) пропускаются, пустая строка
     * у необязательного поля — его значение по умолчанию.
     *
     * @param array<string, mixed> $data
     *
     * @throws BlockException
     */
    public static function fromArray(array $data): static
    {
        $class = new ReflectionClass(static::class);
        $arguments = collect($class->getConstructor()?->getParameters() ?? [])
            ->mapWithKeys(fn (ReflectionParameter $parameter): array => self::argument($parameter, $data))
            ->all();

        return $class->newInstanceArgs($arguments);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws BlockException
     *
     * @return array<string, mixed>
     */
    private static function argument(ReflectionParameter $parameter, array $data): array
    {
        $name = $parameter->getName();
        $value = $data[$name] ?? null;

        if ($value !== null && $value !== '') {
            return [$name => $value];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return [];
        }

        if ($parameter->allowsNull()) {
            return [$name => null];
        }

        throw BlockException::missingField(static::class, $name);
    }
}
