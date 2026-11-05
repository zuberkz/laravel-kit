<?php

namespace Zuber\Kit\Leads\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Zuber\Kit\Leads\Enums\LeadStatusEnum;

/**
 * Заявка с сайта.
 *
 * @property int                  $id
 * @property string               $name
 * @property string               $phone
 * @property string|null          $message
 * @property string|null          $source
 * @property string|null          $url
 * @property LeadStatusEnum       $status
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable(['name', 'phone', 'message', 'source', 'url', 'status'])]
final class Lead extends Model
{
    protected function casts(): array
    {
        return [
            'status' => LeadStatusEnum::class,
        ];
    }
}
