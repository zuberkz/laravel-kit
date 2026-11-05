<?php

namespace Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;
use Zuber\Kit\Core\Filament\Support\PanelAccessSupport;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Pages\CreateSharedBlock;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Pages\EditSharedBlock;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Pages\ListSharedBlocks;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Schemas\SharedBlockForm;
use Zuber\Kit\Modules\Pages\Filament\Resources\SharedBlocks\Tables\SharedBlocksTable;
use Zuber\Kit\Modules\Pages\Models\SharedBlock;
use Zuber\Kit\Modules\Pages\PagesPlugin;
use Zuber\Kit\Modules\Pages\Services\SharedBlockService;

/**
 * Библиотека общих блоков: шапка, подвал и разделы, которые повторяются на многих страницах.
 *
 * @extends resource<SharedBlock>
 */
final class SharedBlockResource extends Resource
{
    protected static ?string $model = SharedBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare2Stack;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('общий блок');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Общие блоки');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return PagesPlugin::current()->getNavigationGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return SharedBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SharedBlocksTable::configure($table);
    }

    /**
     * @return Builder<SharedBlock>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('pages');
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $allowed = PanelAccessSupport::allows(PagesPlugin::current()->getManagePermission());

        if ($allowed && $action === 'delete' && $record instanceof SharedBlock) {
            return app(SharedBlockService::class)->canDelete($record)
                ? Response::allow()
                : Response::deny(__('Блок стоит на страницах или в шапке и подвале — сначала уберите его оттуда.'));
        }

        return PanelAccessSupport::response($allowed);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSharedBlocks::route('/'),
            'create' => CreateSharedBlock::route('/create'),
            'edit' => EditSharedBlock::route('/{record}/edit'),
        ];
    }
}
