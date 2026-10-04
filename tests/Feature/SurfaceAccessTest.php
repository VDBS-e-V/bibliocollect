<?php

declare(strict_types=1);

it('redirects protected surfaces to login without an online account', function (string $uri): void {
    $this->get($uri)->assertRedirect(route('login'));
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
