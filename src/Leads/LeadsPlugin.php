<?php

namespace Zuber\Kit\Leads;

use BackedEnum;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use UnitEnum;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\LeadFormResource;
use Zuber\Kit\Leads\Filament\Resources\Leads\LeadResource;

/**
 * Разделы «Заявки» и «Формы заявок» в панели Filament:
 *
 *     ->plugin(LeadsPlugin::make()->managePermission(PermissionEnum::MANAGE_LEADS)->navigationGroup(NavigationGroupEnum::SITE))
 */
final class LeadsPlugin implements Plugin
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
        $plugin = $panel?->hasPlugin('kit-leads') ? $panel->getPlugin('kit-leads') : null;

        return $plugin instanceof self ? $plugin : new self;
    }

    public function getId(): string
    {
        return 'kit-leads';
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
        $panel->resources([LeadResource::class, LeadFormResource::class]);
    }

    public function boot(Panel $panel): void {}
}
