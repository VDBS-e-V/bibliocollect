<?php

declare(strict_types=1);

namespace App\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setzt Sicherheits-Header (die Kamera ist nur für diese Seite erlaubt: Kamera-Scan beim Einsortieren). Die strenge Content-Security-Policy gilt nur im Produktivbetrieb, weil der Vite-Entwicklungsserver
 * Skripte und Verbindungen von einer eigenen Adresse nachlädt.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        if (app()->environment('production')) {
            // Bilder nur von dieser Seite (Cover liegen lokal); Skripte und Schriften nur eigene; kein Einbetten durch Dritte.
            $headers['Content-Security-Policy'] = "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

            if ($request->isSecure()) {
                $headers['Strict-Transport-Security'] = 'max-age=15552000; includeSubDomains';
            }
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
