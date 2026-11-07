<?php

use Illuminate\Http\Client\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Zuber\Kit\Modules\Leads\Enums\LeadStatusEnum;
use Zuber\Kit\Modules\Leads\Models\Lead;
use Zuber\Kit\Modules\Leads\Notifications\NewLeadNotification;
use Zuber\Kit\Modules\Leads\Services\LeadService;
use Zuber\Kit\Modules\Telegram\Clients\TelegramClient;
use Zuber\Kit\Modules\Telegram\Exceptions\TelegramUnavailableException;
use Zuber\Kit\Modules\Telegram\Notifications\TelegramChannel;

beforeEach(function (): void {
    Http::preventStrayRequests();
    config(['kit.telegram' => ['bot_token' => '123:secret', 'chat_id' => '-1001234567890', 'thread_id' => '7']]);
});

/**
 * @param array<string, mixed> $attributes
 */
function telegramLead(array $attributes = []): Lead
{
    return Lead::query()->create([
        'name' => 'Айгерим',
        'phone' => '+77071122536',
        'answers' => [['label' => 'Направление', 'value' => 'Каньон'], ['label' => 'Телефон', 'value' => '+7 707 112-25-36']],
        'source' => 'Первый экран',
        'url' => 'https://example.kz/charyn',
        'status' => LeadStatusEnum::NEW,
        ...$attributes,
    ]);
}

it('sends the lead by mail and to the telegram topic with a link to the admin', function (): void {
    Notification::fake();

    app(LeadService::class)->notifyTeam($lead = telegramLead());

    Notification::assertSentOnDemand(
        NewLeadNotification::class,
        fn (NewLeadNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notification->lead->is($lead)
            && $channels === ['mail', TelegramChannel::class]
            && $notifiable->routes['mail'] === ['team@example.kz']
            && $notifiable->routes[TelegramChannel::ROUTE] === ['chat_id' => '-1001234567890', 'thread_id' => 7]
            && str_ends_with((string) $notification->adminUrl, '/leads'),
    );
});

it('sends to telegram alone when there are no mail addresses', function (): void {
    Notification::fake();
    config(['kit.leads.mail_to' => []]);

    app(LeadService::class)->notifyTeam(telegramLead());

    Notification::assertSentOnDemand(NewLeadNotification::class, fn (NewLeadNotification $notification, array $channels): bool => $channels === [TelegramChannel::class]);
});

it('keeps telegram off without a bot or a chat', function (array $telegram): void {
    Notification::fake();
    config(['kit.telegram' => $telegram]);

    app(LeadService::class)->notifyTeam(telegramLead());

    Notification::assertSentOnDemand(NewLeadNotification::class, fn (NewLeadNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === ['mail'] && $notification->adminUrl === null);
})->with([
    'no token' => [['bot_token' => null, 'chat_id' => '-100123', 'thread_id' => null]],
    'no chat' => [['bot_token' => '123:secret', 'chat_id' => null, 'thread_id' => null]],
]);

it('formats the answers, escapes what the visitor typed and links to the admin', function (): void {
    $html = (new NewLeadNotification(telegramLead(['answers' => [['label' => 'Комментарий', 'value' => '<b>жирно</b> & ссылка']]]), 'https://example.kz/admin/leads'))->toTelegram(new AnonymousNotifiable)->toHtml();

    expect($html)
        ->toStartWith('🔔 <b>Заявка с сайта</b>')
        ->toContain('<i>Первый экран</i>')
        ->toContain('<b>Комментарий:</b> &lt;b&gt;жирно&lt;/b&gt; &amp; ссылка')
        ->toContain('<b>Страница:</b> https://example.kz/charyn')
        ->toEndWith('<a href="https://example.kz/admin/leads">Открыть в админке</a>');
});

it('falls back to the contacts and cuts a long lead by whole lines', function (): void {
    $short = (new NewLeadNotification(telegramLead(['answers' => []])))->toTelegram(new AnonymousNotifiable)->toHtml();
    $long = (new NewLeadNotification(telegramLead(['answers' => collect(range(1, 40))->map(fn (int $i): array => ['label' => "Поле {$i}", 'value' => str_repeat('я', 290)])->all()]), 'https://example.kz/admin/leads'))->toTelegram(new AnonymousNotifiable)->toHtml();

    expect($short)->toContain('<b>Имя:</b> Айгерим')->toContain('<b>Телефон:</b> +77071122536')->not->toContain('Открыть в админке')
        ->and(mb_strlen($long))->toBeLessThanOrEqual(4000)
        ->and($long)->toContain("…\n")->toEndWith('Открыть в админке</a>');
});

it('posts to the bot api with the chat, the topic and html markup', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);

    Notification::sendNow(
        (new AnonymousNotifiable)->route(TelegramChannel::ROUTE, ['chat_id' => '-1001234567890', 'thread_id' => 7]),
        new NewLeadNotification(telegramLead()),
    );

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.telegram.org/bot123:secret/sendMessage'
        && $request['chat_id'] === '-1001234567890'
        && $request['message_thread_id'] === 7
        && $request['parse_mode'] === 'HTML'
        && str_contains((string) $request['text'], 'Каньон'));
});

it('turns a telegram refusal into its own exception with the reason', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)]);

    expect(fn () => app(TelegramClient::class)->sendMessage('-100', 'Привет'))
        ->toThrow(TelegramUnavailableException::class, 'chat not found');
});
