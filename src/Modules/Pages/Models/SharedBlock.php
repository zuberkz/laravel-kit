<?php

namespace Zuber\Kit\Modules\Pages\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Zuber\Kit\Modules\Pages\Enums\SharedBlockPositionEnum;

/**
 * Общий блок: правится в одном месте и стоит на многих страницах.
 *
 * @property int                          $id
 * @property string                       $name
 * @property string                       $type
 * @property array<string, mixed>|null    $data
 * @property SharedBlockPositionEnum|null $position
 * @property int|null                     $pages_count
 * @property CarbonInterface|null         $created_at
 * @property CarbonInterface|null         $updated_at
 */
#[Fillable(['name', 'type', 'data', 'position'])]
final class SharedBlock extends Model
{
    /**
     * @return BelongsToMany<Page, $this>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class);
    }

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'position' => SharedBlockPositionEnum::class,
        ];
    }
}
