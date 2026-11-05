<?php

namespace Zuber\Kit\Leads\Filament\Resources\LeadForms;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;
use Zuber\Kit\Filament\Support\PanelAccessSupport;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\Pages\CreateLeadForm;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\Pages\EditLeadForm;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\Pages\ListLeadForms;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\Schemas\LeadFormForm;
use Zuber\Kit\Leads\Filament\Resources\LeadForms\Tables\LeadFormsTable;
use Zuber\Kit\Leads\LeadsPlugin;
use Zuber\Kit\Leads\Models\LeadForm;

/**
 * Формы заявок, собранные без программиста: поля из элементов Filament.
 *
 * @extends resource<LeadForm>
 */
final class LeadFormResource extends Resource
{
    protected static ?string $model = LeadForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('форму заявки');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Формы заявок');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return LeadsPlugin::current()->getNavigationGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return LeadFormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadFormsTable::configure($table);
    }

    /**
     * @return Builder<LeadForm>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('leads');
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return PanelAccessSupport::response(PanelAccessSupport::allows(LeadsPlugin::current()->getManagePermission()));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadForms::route('/'),
            'create' => CreateLeadForm::route('/create'),
            'edit' => EditLeadForm::route('/{record}/edit'),
        ];
    }
}
