<?php

namespace Zuber\Kit\Leads\Data;

use Illuminate\Support\Arr;

/**
 * Заявка из формы сайта. Телефон уже нормализован полем PhoneInput: +77071234567.
 */
final readonly class LeadData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $message = null,
        public ?string $source = null,
        public ?string $url = null,
    ) {}

    /**
     * @param array<string, mixed> $state
     */
    public static function fromForm(array $state, ?string $source, ?string $url): self
    {
        $message = $state['message'] ?? null;

        return new self(
            name: trim(Arr::string($state, 'name')),
            phone: Arr::string($state, 'phone'),
            message: is_string($message) && trim($message) !== '' ? trim($message) : null,
            source: $source === '' ? null : $source,
            url: $url,
        );
    }
}
