<?php

namespace Zuber\Kit\Leads\Data;

use Zuber\Kit\Leads\Enums\LeadFieldTypeEnum;
use Zuber\Kit\Leads\Models\LeadForm;

/**
 * Форма заявки для сайта: поля и тексты. Без своих форм — встроенная «Обратный звонок».
 */
final readonly class LeadFormDefinitionData
{
    /**
     * @param array<int, LeadFieldData> $fields
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public array $fields,
        public string $buttonLabel,
        public string $successMessage,
        public ?string $mailTo = null,
    ) {}

    public static function callback(): self
    {
        return new self(
            id: null,
            name: __('Обратный звонок'),
            fields: [
                new LeadFieldData(LeadFieldTypeEnum::NAME, 'name', __('Имя'), required: true),
                new LeadFieldData(LeadFieldTypeEnum::PHONE, 'phone', __('Телефон'), required: true),
                new LeadFieldData(LeadFieldTypeEnum::TEXTAREA, 'message', __('Комментарий')),
            ],
            buttonLabel: __('Отправить заявку'),
            successMessage: __('Мы свяжемся с вами в ближайшее рабочее время.'),
        );
    }

    public static function fromModel(LeadForm $form): self
    {
        $default = self::callback();

        return new self(
            id: $form->id,
            name: $form->name,
            fields: collect($form->fields)
                ->map(fn (mixed $item): ?LeadFieldData => is_array($item) ? LeadFieldData::fromBuilderItem($item) : null)
                ->filter()
                ->values()
                ->all(),
            buttonLabel: $form->button_label ?: $default->buttonLabel,
            successMessage: $form->success_message ?: $default->successMessage,
            mailTo: $form->mail_to,
        );
    }
}
