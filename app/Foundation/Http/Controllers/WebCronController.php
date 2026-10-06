<?php

declare(strict_types=1);

namespace App\Foundation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

/** Löst `app:cron` per URL aus, für Anbieter, bei denen ein Cronjob nur eine Adresse aufrufen kann. */
final class WebCronController
{
    public function __invoke(Request $request, ?string $token = null): Response
    {
        $given = $token
            ?? $request->header('X-Api-Key')
            ?? $request->bearerToken()
            ?? '';

        // Gleiche Antwort für falschen und fehlenden Key, damit die Adresse nichts verrät.
        abort_unless($given !== '' && hash_equals((string) config('hosting.cron_token'), $given), 404);

        $output = new BufferedOutput;
        Artisan::call('app:cron', ['--queue-seconds' => 20], $output);

        return response(trim($output->fetch()), 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
