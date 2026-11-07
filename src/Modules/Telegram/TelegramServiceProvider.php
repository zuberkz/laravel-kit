<?php

namespace Zuber\Kit\Modules\Telegram;

use Illuminate\Support\ServiceProvider;
use Zuber\Kit\Modules\Telegram\Commands\TelegramCommand;

/**
 * Уведомления в Telegram (kit.telegram): команда kit:telegram для настройки — чаты, тема группы, пробное сообщение.
 */
final class TelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([TelegramCommand::class]);
        }
    }
}
