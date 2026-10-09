<?php

declare(strict_types=1);

namespace App\Foundation\Http\Controllers;

use App\Foundation\Support\AlertService;
use App\Foundation\Update\UpdateException;
use App\Foundation\Update\UpdateManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Abschluss eines Updates in einer neuen Anfrage (mit dem neuen Code). Erreichbar auch im Wartungsmodus, aber nur mit dem
 * einmaligen Schlüssel des wartenden Updates.
 */
final class UpdateFinishController
{
    public function __invoke(string $token, UpdateManager $updates, AlertService $alerts): RedirectResponse|Response
    {
        try {
            $result = $updates->finish($token);
        } catch (UpdateException $exception) {
            $alerts->notify('update-finish-failed', 'Der Abschluss des Updates ist fehlgeschlagen', [$exception->getMessage(), 'Zeit: '.now()->toDateTimeString()]);

            return response('<!doctype html><html lang="de"><meta charset="utf-8"><title>Update</title><body style="font-family:Arial,sans-serif;max-width:40rem;margin:3rem auto;padding:0 1rem"><h1>Update nicht abgeschlossen</h1><p>'.e($exception->getMessage()).'</p><p>Was jetzt hilft: Die Datenbank-Sicherung von vor dem Update liegt unter <code>storage/app/backups</code> und lässt sich in phpMyAdmin einspielen. Den Wartungsmodus beendest du, indem du die Datei <code>storage/framework/down</code> per FTP löschst.</p></body></html>', 500)->header('Cache-Control', 'no-store');
        }

        return redirect()->route('administration.update.index')->with('update_success', $result['message']);
    }
}
