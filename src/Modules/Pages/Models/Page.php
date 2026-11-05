<?php

namespace Zuber\Kit\Modules\Pages\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Zuber\Kit\Modules\Pages\Enums\PageStatusEnum;

/**
 * Страница конструктора.
 *
 * @property int                           $id
 * @property string                        $title
 * @property string                        $path
 * @property PageStatusEnum                $status
 * @property array<int|string, mixed>|null $blocks          блоки конструктора: [{type, data}]
 * @property bool                          $show_header
 * @property bool                          $show_footer
 * @property string|null                   $seo_title
 * @property string|null                   $seo_description
 * @property string|null                   $og_image
 * @property bool                          $noindex
 * @property CarbonInterface|null          $created_at
 * @property CarbonInterface|null          $updated_at
 */
#[Fillable(['title', 'path', 'status', 'blocks', 'show_header', 'show_footer', 'seo_title', 'seo_description', 'og_image', 'noindex'])]
final class Page extends Model
{
    public const string HOME_PATH = '/';

    /**
     * @return BelongsToMany<SharedBlock, $this>
     */
    public function sharedBlocks(): BelongsToMany
    {
        return $this->belongsToMany(SharedBlock::class);
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatusEnum::PUBLISHED;
    }

    protected function casts(): array
    {
        return [
            'status' => PageStatusEnum::class,
            'blocks' => 'array',
            'show_header' => 'boolean',
            'show_footer' => 'boolean',
            'noindex' => 'boolean',
        ];
    }
}
