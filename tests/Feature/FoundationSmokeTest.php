<?php

declare(strict_types=1);

it('serves the public bootstrap page', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('BiblioCollect');
});

it('exposes the Laravel liveness endpoint', function (): void {
    $this->get('/up')->assertOk();
});
