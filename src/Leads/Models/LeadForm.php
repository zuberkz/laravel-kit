<?php

namespace Zuber\Kit\Leads\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Форма заявки, собранная в админке.
 *
 * @property int                      $id
 * @property string                   $name
 * @property array<int|string, mixed> $fields          поля из конструктора: [{type, data}]
 * @property string|null              $button_label
 * @property string|null              $success_message
 * @property string|null              $mail_to
 * @property int|null                 $leads_count
 * @property CarbonInterface|null     $created_at
 * @property CarbonInterface|null     $updated_at
 */
#[Fillable(['name', 'fields', 'button_label', 'success_message', 'mail_to'])]
final class LeadForm extends Model
{
    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    protected function casts(): array
    {
        return [
            'fields' => 'array',
        ];
    }
}
