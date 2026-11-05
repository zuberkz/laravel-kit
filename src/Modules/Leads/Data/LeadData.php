<?php

namespace Zuber\Kit\Modules\Leads\Data;

/**
 * Заявка с формы сайта: контакты для списка и звонка плюс снимок всех ответов.
 */
final readonly class LeadData
{
    /**
     * @param array<int, array{label: string, value: string}> $answers
     */
    public function __construct(
        public ?int $formId,
        public array $answers,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $source = null,
        public ?string $url = null,
    ) {}
}
