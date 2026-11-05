<?php

namespace Zuber\Kit\Leads\Actions;

use Zuber\Kit\Leads\Data\LeadFormData;
use Zuber\Kit\Leads\Exceptions\LeadFormWithoutContactException;
use Zuber\Kit\Leads\Models\LeadForm;
use Zuber\Kit\Leads\Services\LeadFormService;

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
