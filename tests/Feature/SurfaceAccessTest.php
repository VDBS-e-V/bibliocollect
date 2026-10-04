<?php

declare(strict_types=1);

it('keeps protected surfaces closed without an authorized online account', function (string $uri): void {
    $this->get($uri)->assertStatus(401);
})->with([
    '/konto',
    '/betrieb',
    '/verwaltung',
]);

it('does not register local preview routes in the test environment', function (): void {
    $this->get('/_preview/portal')->assertNotFound();
    $this->get('/_preview/pos')->assertNotFound();
    $this->get('/_preview/administration')->assertNotFound();
});
