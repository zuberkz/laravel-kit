<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Zuber\Kit\Modules\Telegram\Contracts\TelegramNotificationInterface;
use Zuber\Kit\Modules\Telegram\Data\TelegramMessageData;
use Zuber\Kit\Modules\Telegram\Notifications\TelegramChannel;
use Zuber\Kit\Modules\Telegram\Services\TelegramService;

beforeEach(function (): void {
    Http::preventStrayRequests();
    config(['kit.telegram' => ['bot_token' => '123:secret', 'chat_id' => '-1001234567890', 'thread_id' => '7']]);
});

/** Уведомление сайта о своём событии (заказ) — не из модуля заявок кита */
function telegramOrderNotification(): BaseNotification&TelegramNotificationInterface
{
    return new class extends BaseNotification implements ShouldQueue, TelegramNotificationInterface
    {
        use Queueable;

        /**
         * @return array<int, string>
         */
        public function via(object $notifiable): array
        {
            return [TelegramChannel::class];
        }

        public function toTelegram(object $notifiable): TelegramMessageData
        {
            return new TelegramMessageData(
                title: 'Заказ №15',
                fields: [['label' => 'Покупатель', 'value' => 'Айгерим <script>']],
                text: 'Позвоните после 18:00 & уточните адрес',
                link: 'https://example.kz/admin/orders/15',
                linkLabel: 'Открыть заказ',
                icon: '🛒',
            );
        }
    };
}

it('sends any notification of the site to the team chat and topic', function (): void {
    Notification::fake();

    app(TelegramService::class)->notify($order = telegramOrderNotification());

    Notification::assertSentOnDemand(
        $order::class,
        fn (BaseNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === [TelegramChannel::class]
            && $notifiable->routes[TelegramChannel::ROUTE] === ['chat_id' => '-1001234567890', 'thread_id' => 7],
    );
});

it('sends nothing while telegram is not configured', function (): void {
    Notification::fake();
    config(['kit.telegram' => ['bot_token' => null, 'chat_id' => '-100123', 'thread_id' => null]]);

    app(TelegramService::class)->notify(telegramOrderNotification());

    Notification::assertNothingSent();
});

it('posts the message of the notification with escaped values and the link', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);

    Notification::sendNow((new AnonymousNotifiable)->route(TelegramChannel::ROUTE, ['chat_id' => '-100', 'thread_id' => 7]), telegramOrderNotification());

    Http::assertSent(fn (Request $request): bool => $request['message_thread_id'] === 7
        && $request['text'] === "🛒 <b>Заказ №15</b>\n\n<b>Покупатель:</b> Айгерим &lt;script&gt;\n\nПозвоните после 18:00 &amp; уточните адрес\n\n<a href=\"https://example.kz/admin/orders/15\">Открыть заказ</a>");
});

it('cuts a long message by whole lines and keeps the link', function (): void {
    $html = (new TelegramMessageData(
        title: 'Бриф',
        fields: collect(range(1, 40))->map(fn (int $i): array => ['label' => "Вопрос {$i}", 'value' => str_repeat('ответ ', 100)])->all(),
        link: 'https://example.kz/admin/leads/1',
        linkLabel: 'Открыть в админке',
    ))->toHtml();

    expect(mb_strlen($html))->toBeLessThanOrEqual(4000)
        ->and(substr_count($html, '<b>'))->toBe(substr_count($html, '</b>'))
        ->and($html)->toContain("…\n")->toEndWith('Открыть в админке</a>');
});
