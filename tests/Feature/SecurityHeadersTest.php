<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sends basic security headers on every response', function (): void {
    $response = $this->get(route('public.catalog.index'))->assertOk();

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Permissions-Policy'))->toContain('camera=()')
        ->and($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

it('adds a strict content security policy and hsts in production over https', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');

    $response = $this->get('https://localhost/katalog')->assertOk();
    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("script-src 'self'")
        ->and($csp)->toContain("frame-ancestors 'self'")
        ->and($csp)->toContain("object-src 'none'")
        ->and($response->headers->get('Strict-Transport-Security'))->toContain('max-age=');
});

it('does not use inline scripts that the policy would block', function (): void {
    $html = $this->get(route('public.catalog.index'))->getContent();

    expect(preg_match('/<script(?![^>]*\bsrc=)[^>]*>/i', (string) $html))->toBe(0)
        ->and(preg_match('/\son(click|change|submit|load)=/i', (string) $html))->toBe(0);
});

it('lets the doctor flag demo accounts in production', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');
    User::factory()->create(['email' => 'staff@demo.bibliocollect.test']);

    $this->artisan('app:doctor')->expectsOutputToContain('Demo-Konten')->assertFailed();
});
