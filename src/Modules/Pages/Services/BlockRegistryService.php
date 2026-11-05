<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Zuber\Kit\Modules\Pages\Blocks\Block;

/**
 * Блоки проекта: классы из app/Blocks (или список kit.pages.blocks), по типу.
 */
final readonly class BlockRegistryService
{
    public function __construct(
        private Repository $config,
        private Application $app,
    ) {}

    /**
     * @return array<string, class-string<Block>>
     */
    public function all(): array
    {
        return once(fn (): array => collect($this->classes())
            ->filter(fn (string $class): bool => is_subclass_of($class, Block::class))
            ->mapWithKeys(fn (string $class): array => [$class::type() => $class])
            ->all());
    }

    /**
     * @return class-string<Block>|null
     */
    public function find(string $type): ?string
    {
        return $this->all()[$type] ?? null;
    }

    /**
     * Типы и подписи для выбора в админке.
     *
     * @return array<string, string>
     */
    public function labels(): array
    {
        return collect($this->all())->map(fn (string $class): string => $class::label())->all();
    }

    /**
     * @return array<int, string>
     */
    private function classes(): array
    {
        $configured = $this->config->get('kit.pages.blocks');

        if (is_array($configured)) {
            return collect($configured)->filter(fn (mixed $class): bool => is_string($class))->values()->all();
        }

        $directory = $this->app->path('Blocks');

        if (! is_dir($directory)) {
            return [];
        }

        return collect(Finder::create()->files()->name('*Block.php')->in($directory)->sortByName())
            ->map(fn (mixed $file): string => $file instanceof SplFileInfo ? $this->app->getNamespace() . 'Blocks\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()) : '')
            ->filter()
            ->values()
            ->all();
    }
}
