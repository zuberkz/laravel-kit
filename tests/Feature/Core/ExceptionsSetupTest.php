<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Zuber\Kit\Core\Exceptions\DomainException;
use Zuber\Kit\Core\Setup\ExceptionsSetup;

beforeEach(function (): void {
    $setup = new ExceptionsSetup;
    $setup(new Exceptions(app(ExceptionHandler::class)));

    Route::middleware('web')->post('/test/refuse', fn () => throw new class('Заказ уже оплачен.') extends DomainException
    {
        public function context(): array
        {
            return ['order_id' => 7];
        }
    });
});

it('sends the visitor back with the refusal message', function (): void {
    $this->from('/form')
        ->post('/test/refuse', ['comment' => 'текст'])
        ->assertRedirect('/form')
        ->assertSessionHas('error', 'Заказ уже оплачен.')
        ->assertSessionHasInput('comment', 'текст');
});

it('answers a JSON request with 422 and the message', function (): void {
    $this->postJson('/test/refuse')
        ->assertUnprocessable()
        ->assertExactJson(['message' => 'Заказ уже оплачен.']);
});

it('logs the refusal to the domain channel, not as an error', function (): void {
    $log = storage_path('logs/domain-test.log');
    File::delete($log);
    config(['logging.channels.domain' => ['driver' => 'single', 'path' => $log, 'level' => 'info']]);

    $this->post('/test/refuse');

    expect(File::get($log))->toContain('INFO: Заказ уже оплачен.')
        ->toContain('"order_id":7')
        ->toContain('/test/refuse"');

    File::delete($log);
});
