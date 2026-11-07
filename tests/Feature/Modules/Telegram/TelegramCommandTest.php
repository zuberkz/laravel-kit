<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::preventStrayRequests();
    config(['kit.telegram' => ['bot_token' => '123:secret', 'chat_id' => '-1001234567890', 'thread_id' => null]]);
});

it('lists the chats and the topics the bot has seen', function (): void {
    Http::fake(['api.telegram.org/*/getUpdates' => Http::response(['ok' => true, 'result' => [
        ['update_id' => 1, 'my_chat_member' => ['chat' => ['id' => -1001234567890, 'type' => 'supergroup', 'title' => 'Заявки']]],
        ['update_id' => 2, 'message' => [
            'message_id' => 5,
            'message_thread_id' => 9,
            'is_topic_message' => true,
            'chat' => ['id' => -1001234567890, 'type' => 'supergroup', 'title' => 'Заявки'],
            'reply_to_message' => ['forum_topic_created' => ['name' => 'Заявки с сайта']],
        ]],
        ['update_id' => 3, 'message' => ['message_id' => 6, 'chat' => ['id' => 555, 'type' => 'private', 'first_name' => 'Айгерим']]],
    ]])]);

    $this->artisan('kit:telegram')
        ->expectsOutputToContain('-1001234567890')
        ->expectsOutputToContain('thread 9')
        ->expectsOutputToContain('Айгерим')
        ->assertSuccessful();
});

it('asks to write to the bot when nobody has', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

    $this->artisan('kit:telegram')->expectsOutputToContain('Боту ещё никто не писал')->assertFailed();
});

it('creates the topic in the group and prints its id', function (): void {
    Http::fake(['api.telegram.org/*/createForumTopic' => Http::response(['ok' => true, 'result' => ['message_thread_id' => 42, 'name' => 'Заявки с сайта']])]);

    $this->artisan('kit:telegram', ['--topic' => 'Заявки с сайта'])
        ->expectsOutputToContain('TELEGRAM_THREAD_ID=42')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request['chat_id'] === '-1001234567890' && $request['name'] === 'Заявки с сайта');
});

it('sends a test message right away', function (): void {
    Http::fake(['api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);

    $this->artisan('kit:telegram', ['--test' => true])->expectsOutputToContain('Пробное сообщение отправлено')->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => str_contains((string) $request['text'], 'Тестовое сообщение'));
});

it('explains what to set when telegram is not configured', function (): void {
    config(['kit.telegram' => ['bot_token' => null, 'chat_id' => null, 'thread_id' => null]]);

    $this->artisan('kit:telegram', ['--test' => true])->expectsOutputToContain('TELEGRAM_BOT_TOKEN')->assertFailed();
    $this->artisan('kit:telegram', ['--topic' => 'Заявки'])->expectsOutputToContain('kit.telegram')->assertFailed();
});

it('shows the telegram error instead of a stack trace', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401)]);

    $this->artisan('kit:telegram')->expectsOutputToContain('Unauthorized')->assertFailed();
});
