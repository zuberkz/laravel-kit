<?php

namespace Zuber\Kit\Modules\Pages\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Zuber\Kit\Modules\Pages\Repositories\PageRepository;
use Zuber\Kit\Modules\Pages\Repositories\SharedBlockRepository;

/**
 * Картинки блоков: адрес для шаблона и удаление файлов, которые после сохранения больше нигде не
 * упомянуты (копия страницы ссылается на те же файлы — их не трогаем).
 */
final readonly class BlockImageService
{
    public function __construct(
        private Factory $filesystems,
        private Repository $config,
        private PageRepository $pages,
        private SharedBlockRepository $sharedBlocks,
    ) {}

    public function url(?string $path): ?string
    {
        return $path === null || $path === '' ? null : $this->disk()->url($path);
    }

    public function directory(): string
    {
        $directory = $this->config->get('kit.pages.image_directory');

        return is_string($directory) ? $directory : 'blocks';
    }

    public function diskName(): string
    {
        $disk = $this->config->get('kit.pages.disk');

        return is_string($disk) ? $disk : 'public';
    }

    /**
     * Удалить файлы, которые были в старых данных, но пропали из новых и не упомянуты нигде ещё.
     */
    public function deleteUnused(mixed $before, mixed $after): void
    {
        collect($this->files($before))
            ->diff($this->files($after))
            ->reject(fn (string $path): bool => $this->pages->referencesFile($path) || $this->sharedBlocks->referencesFile($path))
            ->each(fn (string $path): bool => $this->disk()->delete($path));
    }

    /**
     * Пути картинок в данных блоков: строки из каталога картинок кита.
     *
     * @return array<int, string>
     */
    private function files(mixed $data): array
    {
        $prefix = $this->directory() . '/';

        return collect(is_array($data) ? $data : [$data])
            ->flatten()
            ->filter(fn (mixed $value): bool => is_string($value) && str_starts_with($value, $prefix))
            ->unique()
            ->values()
            ->all();
    }

    private function disk(): Filesystem
    {
        return $this->filesystems->disk($this->diskName());
    }
}
