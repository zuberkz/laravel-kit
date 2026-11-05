<?php

namespace Zuber\Kit\Leads\Filament\Resources\Leads;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;
use Zuber\Kit\Filament\Support\PanelAccessSupport;
use Zuber\Kit\Leads\Filament\Resources\Leads\Pages\ListLeads;
use Zuber\Kit\Leads\Filament\Resources\Leads\Tables\LeadsTable;
use Zuber\Kit\Leads\LeadsPlugin;
use Zuber\Kit\Leads\Models\Lead;
use Zuber\Kit\Leads\Repositories\LeadRepository;

/**
 * Заявки с сайта: список, «Обработана», удаление. Создаёт заявки только форма на сайте.
 *
 * @extends resource<Lead>
 */
final class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('заявку');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Заявки');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return LeadsPlugin::current()->getNavigationGroup();
    }

    /**
     * Новые заявки — счётчиком в меню.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = app(LeadRepository::class)->countNew();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    /**
     * @return Builder<Lead>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('leadForm');
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        if ($action === 'create') {
            return Response::deny();
        }

        return PanelAccessSupport::response(PanelAccessSupport::allows(LeadsPlugin::current()->getManagePermission()));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
        ];
    }
}
