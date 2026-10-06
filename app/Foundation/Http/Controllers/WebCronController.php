<?php

declare(strict_types=1);

namespace App\Foundation\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

/** Löst `app:cron` per URL aus, für Anbieter, bei denen ein Cronjob nur eine Adresse aufrufen kann. */
final class WebCronController
{
    public function __invoke(string $token): Response
    {
        abort_unless(hash_equals((string) config('hosting.cron_token'), $token), 404);

        $output = new BufferedOutput;
        Artisan::call('app:cron', ['--queue-seconds' => 20], $output);

        return response(trim($output->fetch()), 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
