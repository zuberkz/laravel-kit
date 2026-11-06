<?php

use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/test/page', fn (): string => 'ok');
    Route::get('/test/framed', fn () => response('ok')->header('X-Frame-Options', 'DENY'));
});

it('adds the security headers to every response', function (): void {
    $this->get('/test/page')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', config('kit.security_headers.permissions_policy'))
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('sends HSTS only in production over https', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    $this->get('http://localhost/test/page')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/test/page')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('covers subdomains with HSTS only when the site asks for it', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');
    config(['kit.security_headers.hsts_include_subdomains' => true]);

    $this->get('https://localhost/test/page')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('keeps a header the response set itself', function (): void {
    $this->get('/test/framed')->assertHeader('X-Frame-Options', 'DENY');
});

it('can be switched off by the project', function (): void {
    config(['kit.security_headers.enabled' => false]);

    $this->get('/test/page')->assertHeaderMissing('X-Content-Type-Options');
});
