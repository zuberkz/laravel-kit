<?php

namespace Zuber\Kit\Modules\Pages\Data;

use Illuminate\Support\Arr;
use Zuber\Kit\Modules\Pages\Enums\SharedBlockPositionEnum;

/**
 * Общий блок из формы админки.
 */
final readonly class SharedBlockData
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $name,
        public string $type,
        public array $data,
        public ?SharedBlockPositionEnum $position = null,
    ) {}

    /**
     * @param array<string, mixed> $state
     */
    public static function fromForm(array $state): self
    {
        $position = $state['position'] ?? null;

        return new self(
            name: Arr::string($state, 'name'),
            type: Arr::string($state, 'type'),
            data: is_array($state['data'] ?? null) ? $state['data'] : [],
            position: $position instanceof SharedBlockPositionEnum ? $position : SharedBlockPositionEnum::tryFrom(is_string($position) ? $position : ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return ['name' => $this->name, 'type' => $this->type, 'data' => $this->data, 'position' => $this->position];
    }
}
