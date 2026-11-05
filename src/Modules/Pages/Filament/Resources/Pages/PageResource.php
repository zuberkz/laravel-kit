<?php

namespace Zuber\Kit\Modules\Pages\Filament\Resources\Pages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use UnitEnum;
use Zuber\Kit\Core\Filament\Support\PanelAccessSupport;
use Zuber\Kit\Modules\Pages\Filament\Resources\Pages\Pages\CreatePage;
use Zuber\Kit\Modules\Pages\Filament\Resources\Pages\Pages\EditPage;
use Zuber\Kit\Modules\Pages\Filament\Resources\Pages\Pages\ListPages;
use Zuber\Kit\Modules\Pages\Filament\Resources\Pages\Schemas\PageForm;
use Zuber\Kit\Modules\Pages\Filament\Resources\Pages\Tables\PagesTable;
use Zuber\Kit\Modules\Pages\Models\Page;
use Zuber\Kit\Modules\Pages\PagesPlugin;

/**
 * @extends resource<Page>
 */
final class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('страницу');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Страницы');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return PagesPlugin::current()->getNavigationGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return PanelAccessSupport::response(PanelAccessSupport::allows(PagesPlugin::current()->getManagePermission()));
    }

    /**
     * Ссылка на страницу, черновика тоже: подписанная, на час.
     */
    public static function previewUrl(Page $page): string
    {
        return URL::temporarySignedRoute('kit.pages.preview', now()->addHour(), ['page' => $page]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
