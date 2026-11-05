<?php

namespace Zuber\Kit\Modules\Spam;

use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;
use Zuber\Kit\Modules\Spam\Services\SpamProtectionService;

/**
 * Защита публичной формы Livewire от ботов (правило security.md): в схему формы — HoneypotField,
 * в начало метода отправки —
 *
 *     if ($this->isSpam()) {
 *         $this->showThanks(); // бот получает обычное «спасибо», ничего не сохраняется
 *
 *         return;
 *     }
 *
 * Ограничение частоты (throttle на маршруте или RateLimiter в action) — отдельно.
 */
trait UsesSpamProtection
{
    /**
     * Когда форму открыли; Locked — клиент его не подменит.
     */
    #[Locked]
    public int $spamFormStartedAt = 0;

    public function mountUsesSpamProtection(): void
    {
        $this->spamFormStartedAt = now()->getTimestamp();
    }

    protected function isSpam(): bool
    {
        $form = $this->getSchema($this->spamProtectedSchema());

        return app(SpamProtectionService::class)->isSpam(
            $form instanceof Schema ? data_get($form->getRawState(), HoneypotField::NAME) : null,
            $this->spamFormStartedAt,
        );
    }

    /**
     * Имя схемы с HoneypotField; у формы с другим именем метод переопределяется.
     */
    protected function spamProtectedSchema(): string
    {
        return 'form';
    }
}
