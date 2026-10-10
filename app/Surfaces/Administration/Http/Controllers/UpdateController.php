<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Update\ReleaseSource;
use App\Foundation\Update\UpdateException;
use App\Foundation\Update\UpdateManager;
use App\Modules\Audit\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** Seite „Update“: Programmpaket hochladen oder per FTP ablegen, einspielen (mit Wartungsmodus) und das nächtliche Einspielen einschalten. */
final class UpdateController
{
    private const RELEASE_CACHE = 'update.release.latest';

    public function index(UpdateManager $updates): Response
    {
        $release = Cache::get(self::RELEASE_CACHE);

        return response()
            ->view('pages.surfaces.administration.update.index', [
                'current' => $updates->currentVersion(),
                'packages' => $updates->packages(),
                'pending' => $updates->pending(),
                'last' => $updates->lastResult(),
                'auto' => $updates->autoEnabled(),
                'autoDownload' => $updates->autoDownloadEnabled(),
                'release' => is_array($release) ? $release + ['newer' => $updates->compare($release['version'] ?? null, $updates->currentVersion()), 'ready' => $updates->hasPackageVersion((string) ($release['version'] ?? ''))] : null,
                'repository' => app(ReleaseSource::class)->repository(),
                'maintenance' => app()->isDownForMaintenance(),
                'directory' => 'storage/app/updates',
                'limit' => ini_get('upload_max_filesize'),
                'php' => PHP_VERSION,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Fragt GitHub nach dem neuesten stabilen Release und merkt es sich kurz für die Anzeige. */
    public function checkRelease(ReleaseSource $source, AuditRecorder $audit, Request $request): RedirectResponse
    {
        // Wiederholtes Klicken darf das knappe anonyme GitHub-API-Limit nicht aufbrauchen.
        $cached = Cache::get(self::RELEASE_CACHE);

        if (is_array($cached) && isset($cached['tag'], $cached['version'])) {
            return redirect()->route('administration.update.index');
        }

        try {
            $release = $source->latest();
        } catch (UpdateException $exception) {
            // Eine erfolgreiche frühere Abfrage bleibt für die Anzeige verfügbar.
            return redirect()->route('administration.update.index')->withErrors(['release' => $exception->getMessage()]);
        }

        Cache::put(self::RELEASE_CACHE, $release, now()->addMinutes(30));
        $audit->record('system.update.release_checked', 'Neuestes Release auf GitHub abgefragt: '.$release['tag'].'.', null, ['tag' => $release['tag']], (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('administration.update.index');
    }

    /** Holt das Paket des abgefragten Releases (Server zu Server), prüft die Prüfsumme und legt es bereit. */
    public function fetchRelease(Request $request, ReleaseSource $source, UpdateManager $updates, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate(['tag' => ['required', 'string', 'regex:/^v\d+\.\d+\.\d+$/']]);
        $release = Cache::get(self::RELEASE_CACHE);

        if (! is_array($release) || ($release['tag'] ?? null) !== $data['tag']) {
            return redirect()->route('administration.update.index')->withErrors(['release' => 'Bitte zuerst auf neue Version prüfen.']);
        }

        @set_time_limit(300);

        try {
            $path = $source->download($release);
        } catch (UpdateException $exception) {
            return redirect()->route('administration.update.index')->withErrors(['release' => $exception->getMessage()]);
        }

        try {
            $name = $updates->store($path, 'bibliocollect-'.$release['tag'].'.zip');
        } catch (UpdateException $exception) {
            @unlink($path);

            return redirect()->route('administration.update.index')->withErrors(['release' => $exception->getMessage()]);
        }

        $audit->record('system.update.downloaded', 'Update-Paket von GitHub geholt: '.$name.'.', null, ['package' => $name, 'tag' => $release['tag']], (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('administration.update.index')->with('update_success', 'Das Paket „'.$name.'“ ist von GitHub geholt, die Prüfsumme stimmt. Du kannst es jetzt einspielen.');
    }

    public function upload(Request $request, UpdateManager $updates, AuditRecorder $audit): RedirectResponse
    {
        $request->validate(['package' => ['required', 'file', 'extensions:zip']], ['package.required' => 'Bitte das Paket (ZIP) auswählen.', 'package.extensions' => 'Bitte eine ZIP-Datei hochladen.', 'package.uploaded' => 'Die Datei ist zu groß für den Server (Grenze: '.ini_get('upload_max_filesize').'). Lege sie stattdessen per FTP in den Ordner storage/app/updates.']);

        try {
            $name = $updates->store((string) $request->file('package')?->getRealPath(), (string) $request->file('package')?->getClientOriginalName());
        } catch (UpdateException $exception) {
            return redirect()->route('administration.update.index')->withErrors(['package' => $exception->getMessage()]);
        }

        $audit->record('system.update.uploaded', 'Update-Paket hochgeladen: '.$name.'.', null, ['package' => $name], (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('administration.update.index')->with('update_success', 'Das Paket „'.$name.'“ liegt bereit. Du kannst es jetzt einspielen.');
    }

    public function apply(Request $request, UpdateManager $updates, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate(['package' => ['required', 'string', 'max:120'], 'confirm' => ['accepted'], 'allow_older' => ['nullable', 'boolean']], ['confirm.accepted' => 'Bitte bestätige, dass die Seite für kurze Zeit nicht erreichbar sein wird.']);

        try {
            $token = $updates->apply($data['package'], $request->boolean('allow_older'));
        } catch (UpdateException $exception) {
            return redirect()->route('administration.update.index')->withErrors(['package' => $exception->getMessage()]);
        }

        $audit->record('system.update.applied', 'Update eingespielt: '.$data['package'].'.', null, ['package' => $data['package']], (int) $request->user()?->getAuthIdentifier());

        // Der Abschluss läuft in einer neuen Anfrage mit dem neuen Code.
        return redirect()->route('update.finish', ['token' => $token]);
    }

    public function destroy(string $name, UpdateManager $updates, AuditRecorder $audit, Request $request): RedirectResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9._-]+\.zip$/', $name) === 1, 404);

        $updates->delete($name);
        $audit->record('system.update.deleted', 'Update-Paket gelöscht: '.$name.'.', null, ['package' => $name], (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('administration.update.index')->with('update_success', 'Das Paket „'.$name.'“ ist gelöscht.');
    }

    public function auto(Request $request, UpdateManager $updates, AuditRecorder $audit): RedirectResponse
    {
        $on = $request->boolean('auto');
        $updates->setAuto($on);
        $updates->setAutoDownload($on && $request->boolean('auto_download'));
        $audit->record('system.update.auto', 'Automatisches Einspielen '.($on ? 'eingeschaltet' : 'ausgeschaltet').'.', null, [], (int) $request->user()?->getAuthIdentifier());

        return redirect()->route('administration.update.index')->with('update_success', $on ? 'Ein neueres Paket wird ab jetzt nachts um 03:15 Uhr automatisch eingespielt.'.($updates->autoDownloadEnabled() ? ' Neue Releases holt der Server dafür selbst von GitHub.' : '') : 'Das automatische Einspielen ist ausgeschaltet.');
    }
}
