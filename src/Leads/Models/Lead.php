<?php

namespace Zuber\Kit\Leads\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Zuber\Kit\Leads\Enums\LeadStatusEnum;

/**
 * Заявка с сайта.
 *
 * @property int                                             $id
 * @property int|null                                        $lead_form_id
 * @property string|null                                     $name
 * @property string|null                                     $phone
 * @property string|null                                     $email
 * @property array<int, array{label: string, value: string}> $answers
 * @property string|null                                     $source
 * @property string|null                                     $url
 * @property LeadStatusEnum                                  $status
 * @property CarbonInterface|null                            $created_at
 * @property CarbonInterface|null                            $updated_at
 * @property-read LeadForm|null $leadForm
 */
#[Fillable(['lead_form_id', 'name', 'phone', 'email', 'answers', 'source', 'url', 'status'])]
final class Lead extends Model
{
    /**
     * @return BelongsTo<LeadForm, $this>
     */
    public function leadForm(): BelongsTo
    {
        return $this->belongsTo(LeadForm::class);
    }

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'status' => LeadStatusEnum::class,
        ];
    }
}
