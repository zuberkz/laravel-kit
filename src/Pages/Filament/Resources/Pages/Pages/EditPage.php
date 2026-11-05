<?php

namespace Zuber\Kit\Pages\Filament\Resources\Pages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Pages\Actions\SavePageAction;
use Zuber\Kit\Pages\Data\PageData;
use Zuber\Kit\Pages\Filament\Resources\Pages\PageResource;
use Zuber\Kit\Pages\Filament\Resources\Pages\Tables\PagesTable;
use Zuber\Kit\Pages\Models\Page;

final class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PagesTable::previewAction(),
            PagesTable::duplicateAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Page $record */
        return app(SavePageAction::class)->handle(PageData::fromForm($data), $record);
    }
}
