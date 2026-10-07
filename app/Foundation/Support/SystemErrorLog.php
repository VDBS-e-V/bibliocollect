<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use App\Foundation\Models\SystemErrorEvent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Hält unerwartete Fehler fest (Klasse, gekürzte Meldung, Stelle, Pfad, Konto) und meldet sie der Administration.
 * Erwartbare Fehler (404, Anmeldung, Berechtigung, Formularprüfung) zählen nicht. Anfragedaten, Cookies und Eingaben
 * werden nie gespeichert.
 */
final readonly class SystemErrorLog
{
    public function __construct(private AlertService $alerts) {}

    public function record(Throwable $exception, ?Request $request = null): void
    {
        if (! $this->isUnexpected($exception)) {
            return;
        }

        try {
            $class = $exception::class;
            $file = $this->relative($exception->getFile());
            $line = $exception->getLine();
            $message = $exception instanceof QueryException ? 'Datenbankfehler (Einzelheiten im Log)' : mb_substr(trim($exception->getMessage()), 0, 300);
            $fingerprint = hash('sha256', $class.'|'.$file.'|'.$line);
            $now = now();

            $existing = SystemErrorEvent::query()->where('fingerprint', $fingerprint)->where('last_seen_at', '>=', $now->copy()->subHour())->first();

            if ($existing instanceof SystemErrorEvent) {
                $existing->forceFill(['occurrences' => $existing->occurrences + 1, 'last_seen_at' => $now, 'message' => $message])->save();
            } else {
                SystemErrorEvent::query()->create([
                    'fingerprint' => $fingerprint,
                    'kind' => 'exception',
                    'class' => mb_substr($class, 0, 190),
                    'message' => $message,
                    'file' => mb_substr($file, 0, 190),
                    'line' => $line,
                    'method' => $request?->method(),
                    'path' => $request !== null ? mb_substr('/'.ltrim($request->path(), '/'), 0, 190) : null,
                    'user_id' => $request?->user()?->getAuthIdentifier() !== null ? (int) $request->user()->getAuthIdentifier() : null,
                    'occurrences' => 1,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);
            }

            $this->alerts->notify('error:'.$fingerprint, 'Unerwarteter Fehler: '.class_basename($class), [
                'Meldung: '.$message,
                'Stelle: '.$file.':'.$line,
                $request !== null ? 'Adresse: '.$request->method().' /'.ltrim($request->path(), '/') : 'Aus einem Hintergrundlauf',
                'Zeit: '.$now->toDateTimeString(),
            ]);
        } catch (Throwable) {
            // Die Fehlerüberwachung darf nie selbst einen Fehler auslösen.
        }
    }

    public function recordFailedJob(string $jobName, Throwable $exception): void
    {
        try {
            $class = $exception::class;
            $file = $this->relative($exception->getFile());
            $fingerprint = hash('sha256', 'job|'.$jobName.'|'.$class.'|'.$file.'|'.$exception->getLine());
            $message = $exception instanceof QueryException ? 'Datenbankfehler (Einzelheiten im Log)' : mb_substr(trim($exception->getMessage()), 0, 300);
            $now = now();

            $existing = SystemErrorEvent::query()->where('fingerprint', $fingerprint)->where('last_seen_at', '>=', $now->copy()->subHour())->first();

            if ($existing instanceof SystemErrorEvent) {
                $existing->forceFill(['occurrences' => $existing->occurrences + 1, 'last_seen_at' => $now, 'message' => $message])->save();
            } else {
                SystemErrorEvent::query()->create([
                    'fingerprint' => $fingerprint,
                    'kind' => 'job',
                    'class' => mb_substr($jobName, 0, 190),
                    'message' => $message,
                    'file' => mb_substr($file, 0, 190),
                    'line' => $exception->getLine(),
                    'occurrences' => 1,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);
            }

            $this->alerts->notify('job:'.$fingerprint, 'Job fehlgeschlagen: '.class_basename($jobName), [
                'Meldung: '.$message,
                'Stelle: '.$file.':'.$exception->getLine(),
                'Zeit: '.$now->toDateTimeString(),
            ]);
        } catch (Throwable) {
            // siehe oben
        }
    }

    /** Älteres als die Aufbewahrungsfrist löschen. */
    public function prune(int $days = 30): int
    {
        return SystemErrorEvent::query()->where('last_seen_at', '<', now()->subDays(max(1, $days)))->delete();
    }

    private function isUnexpected(Throwable $exception): bool
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode() >= 500;
        }

        return ! ($exception instanceof ValidationException
            || $exception instanceof AuthenticationException
            || $exception instanceof AuthorizationException
            || $exception instanceof TokenMismatchException);
    }

    private function relative(string $path): string
    {
        return str_replace([base_path().DIRECTORY_SEPARATOR, '\\'], ['', '/'], $path);
    }
}
