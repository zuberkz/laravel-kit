<?php

namespace Zuber\Kit\Modules\Leads\Actions;

use Zuber\Kit\Modules\Leads\Data\LeadFormData;
use Zuber\Kit\Modules\Leads\Exceptions\LeadFormWithoutContactException;
use Zuber\Kit\Modules\Leads\Models\LeadForm;
use Zuber\Kit\Modules\Leads\Services\LeadFormService;

/**
 * Менеджер собирает форму заявки: поля, тексты, адреса для писем.
 */
final readonly class SaveLeadFormAction
{
    public function __construct(private LeadFormService $forms) {}

    /**
     * @throws LeadFormWithoutContactException
     */
    public function handle(LeadFormData $data, ?LeadForm $form = null): LeadForm
    {
        return $form === null ? $this->forms->create($data) : $this->forms->update($form, $data);
    }
}
