<?php

namespace Zuber\Kit\Modules\Leads\Data;

use Illuminate\Support\Arr;

/**
 * Форма заявки из админки.
 */
final readonly class LeadFormData
{
    /**
     * @param array<int, array<array-key, mixed>> $fields карточки конструктора: {type, data}
     */
    public function __construct(
        public string $name,
        public array $fields,
        public ?string $buttonLabel = null,
        public ?string $successMessage = null,
        public ?string $mailTo = null,
    ) {}

    /**
     * @param array<string, mixed> $state
     */
    public static function fromForm(array $state): self
    {
        return new self(
            name: Arr::string($state, 'name'),
            fields: collect(Arr::wrap($state['fields'] ?? []))->filter(fn (mixed $item): bool => is_array($item))->values()->all(),
            buttonLabel: self::text($state['button_label'] ?? null),
            successMessage: self::text($state['success_message'] ?? null),
            mailTo: self::text($state['mail_to'] ?? null),
        );
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
