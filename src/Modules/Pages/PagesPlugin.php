<?php

namespace Zuber\Kit\Modules\Pages;

use BackedEnum;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use UnitEnum;
use Zuber\Kit\Modules\Pages\Filament\Resources\Pages\PageResource;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\SharedBlockResource;

/**
 * Разделы «Страницы» и «Общие блоки» конструктора в панели Filament:
 *
 *     ->plugin(PagesPlugin::make()->managePermission(PermissionEnum::MANAGE_PAGES)->navigationGroup(NavigationGroupEnum::SITE))
 */
final class PagesPlugin implements Plugin
{
    private ?string $managePermission = null;

    private string|UnitEnum|null $navigationGroup = null;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Плагин текущей панели: его настройки читают ресурсы.
     */
    public static function current(): self
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $plugin = $panel?->hasPlugin('kit-pages') ? $panel->getPlugin('kit-pages') : null;

        return $plugin instanceof self ? $plugin : new self;
    }

    public function getId(): string
    {
        return 'kit-pages';
    }

    public function managePermission(BackedEnum $permission): self
    {
        $this->managePermission = (string) $permission->value;

        return $this;
    }

    public function navigationGroup(string|UnitEnum|null $group): self
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getManagePermission(): ?string
    {
        return $this->managePermission;
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        return $this->navigationGroup;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PageResource::class, SharedBlockResource::class]);
    }

    public function boot(Panel $panel): void {}
}
