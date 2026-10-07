<?php

declare(strict_types=1);

namespace App\Foundation\Http\Controllers;

use App\Foundation\Support\SystemHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Zustand des Systems als JSON für ein Monitoring. Zeigt nur Stände und Zahlen, keine Personen oder Inhalte. */
final class SystemStatusController
{
    public function __invoke(Request $request, SystemHealth $health, ?string $token = null): JsonResponse
    {
        $given = $token ?? $request->header('X-Api-Key') ?? $request->bearerToken() ?? '';
        $expected = is_string(config('hosting.status_token')) && strlen((string) config('hosting.status_token')) >= 24
            ? (string) config('hosting.status_token')
            : (string) config('hosting.cron_token');

        abort_unless($given !== '' && hash_equals($expected, $given), 404);

        $snapshot = $health->snapshot();

        return response()->json($snapshot, $snapshot['status'] === SystemHealth::FAIL ? 503 : 200, ['Cache-Control' => 'no-store']);
    }
}
