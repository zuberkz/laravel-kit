<?php

namespace Zuber\Kit\Spam\Services;

use Illuminate\Contracts\Config\Repository;

/**
 * Бот или человек: бот заполняет скрытое поле-ловушку и отправляет форму мгновенно.
 */
final readonly class SpamProtectionService
{
    public function __construct(private Repository $config) {}

    /**
     * @param int $startedAt когда форму открыли (unix time), 0 — неизвестно
     */
    public function isSpam(mixed $honeypot, int $startedAt): bool
    {
        if (filled($honeypot) || $startedAt === 0) {
            return true;
        }

        $minSeconds = $this->config->get('kit.spam.min_seconds');

        return now()->getTimestamp() - $startedAt < (is_int($minSeconds) ? $minSeconds : 3);
    }
}
