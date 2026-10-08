<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Actions\ImportLegacyCatalogAction;
use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use App\Modules\Catalog\Legacy\LegacyCatalogImportAnalyzer;
use App\Modules\Circulation\Actions\ImportLegacyWishesAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Altbestand ohne Konsole übernehmen: die phpMyAdmin-JSON-Exporte des alten Systems hochladen, prüfen, übernehmen.
 * Dazu die Buchwünsche aus dem Altsystem. Beides ist einmalig gedacht und läuft doppelt ohne Schaden (vorhandene Datensätze werden wiederverwendet).
 */
final class LegacyImportController
{
    private const DIRECTORY = 'legacy-imports';

    private const PARTS = ['media', 'topics', 'signatures'];

    public function create(): Response
    {
        return response()->view('pages.surfaces.pos.catalog.import.legacy', ['report' => null, 'token' => null])->header('Cache-Control', 'private, no-store');
    }

    /** Dateien ablegen und prüfen, ohne etwas zu schreiben. */
    public function analyze(Request $request, LegacyCatalogImportAnalyzer $analyzer): Response|RedirectResponse
    {
        $request->validate([
            'media' => ['required', 'file', 'extensions:json,txt', 'max:51200'],
            'topics' => ['nullable', 'file', 'extensions:json,txt', 'max:51200'],
            'signatures' => ['nullable', 'file', 'extensions:json,txt', 'max:51200'],
        ], [
            'media.required' => 'Bitte die Datei mediaList (JSON) auswählen.',
            '*.extensions' => 'Bitte JSON-Dateien aus phpMyAdmin hochladen.',
            '*.max' => 'Eine Datei darf höchstens 50 MB groß sein.',
        ]);

        $token = Str::lower((string) Str::ulid());

        foreach (self::PARTS as $part) {
            $request->file($part)?->storeAs(self::DIRECTORY, $token.'-'.$part.'.json', 'local');
        }

        try {
            $report = $analyzer->analyze(...$this->paths($token));
        } catch (LegacyCatalogImportException $exception) {
            $this->forget($token);

            return redirect()->route('pos.catalog.legacy.create')->withErrors(['media' => $exception->getMessage()]);
        }

        return response()->view('pages.surfaces.pos.catalog.import.legacy', ['report' => $report, 'token' => $token])->header('Cache-Control', 'private, no-store');
    }

    public function commit(Request $request, string $token, ImportLegacyCatalogAction $import, AuditRecorder $audit): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Bitte bestätige den Import.']);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $paths = $this->paths($token);
        @set_time_limit(600);

        try {
            $report = $import->execute(...$paths);
        } catch (LegacyCatalogImportException $exception) {
            return redirect()->route('pos.catalog.legacy.create')->withErrors(['media' => $exception->getMessage()]);
        }

        $this->forget($token);
        $audit->record('catalog.legacy.imported', 'Altbestand übernommen.', null, $report->summary, (int) $actor->getKey());

        return redirect()->route('pos.catalog.legacy.create')
            ->with('legacy_report', $report->summary)
            ->with('legacy_warnings', $report->warnings);
    }

    public function wishes(Request $request, ImportLegacyWishesAction $import): RedirectResponse
    {
        $request->validate(['wishes' => ['required', 'file', 'extensions:json,txt', 'max:10240']], [
            'wishes.required' => 'Bitte die Datei bookWishes (JSON) auswählen.',
            'wishes.extensions' => 'Bitte eine JSON-Datei aus phpMyAdmin hochladen.',
        ]);

        try {
            $result = $import->execute((string) $request->file('wishes')?->getRealPath());
        } catch (CirculationRuleViolation $exception) {
            return redirect()->route('pos.catalog.legacy.create')->withErrors(['wishes' => $exception->getMessage()]);
        }

        return redirect()->route('pos.catalog.legacy.create')->with('legacy_wishes', "{$result['created']} Buchwünsche übernommen, {$result['skipped']} übersprungen (schon vorhanden oder ohne Titel).");
    }

    /** @return array{0: string, 1: string|null, 2: string|null} */
    private function paths(string $token): array
    {
        abort_unless(preg_match('/^[0-9a-z]{26}$/', $token) === 1, 404);

        $path = static fn (string $part): ?string => Storage::disk('local')->exists(self::DIRECTORY.'/'.$token.'-'.$part.'.json')
            ? Storage::disk('local')->path(self::DIRECTORY.'/'.$token.'-'.$part.'.json')
            : null;

        $media = $path('media');
        abort_if($media === null, 404);

        return [$media, $path('topics'), $path('signatures')];
    }

    private function forget(string $token): void
    {
        Storage::disk('local')->delete(array_map(static fn (string $part): string => self::DIRECTORY.'/'.$token.'-'.$part.'.json', self::PARTS));
    }
}
