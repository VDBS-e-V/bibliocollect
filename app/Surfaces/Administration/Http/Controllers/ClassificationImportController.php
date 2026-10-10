<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Import\Classification\ClassificationImporter;
use App\Modules\Catalog\Import\Classification\ClassificationImportPlanner;
use App\Modules\Catalog\Models\ClassificationImportDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Themen und Regalbretter aus den JSON-Dateien des Altsystems übernehmen, ohne Konsole: erst hochladen und prüfen (nichts wird
 * geschrieben), dann den geprüften Entwurf ausdrücklich bestätigen.
 */
final class ClassificationImportController
{
    public function index(): Response
    {
        return response()
            ->view('pages.surfaces.administration.classification-import.index')
            ->header('Cache-Control', 'private, no-store');
    }

    /** Schritt 1: Dateien speichern und prüfen. Es wird nichts in Katalogdaten geschrieben. */
    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'topics' => ['nullable', 'file', 'max:2048', 'extensions:json,txt'],
            'signatures' => ['nullable', 'file', 'max:2048', 'extensions:json,txt'],
        ], [
            'topics.max' => 'Die Themenliste ist größer als 2 MB.',
            'signatures.max' => 'Die Regalsignaturen sind größer als 2 MB.',
            'topics.extensions' => 'Die Themenliste muss eine .json-Datei sein.',
            'signatures.extensions' => 'Die Regalsignaturen müssen eine .json-Datei sein.',
        ]);

        if (! $request->hasFile('topics') && ! $request->hasFile('signatures')) {
            return back()->withErrors(['topics' => 'Bitte wähle mindestens eine Datei aus.']);
        }

        $this->pruneExpired();

        $draft = new ClassificationImportDraft([
            'user_id' => (int) $request->user()->getKey(),
            'expires_at' => now()->addMinutes(ClassificationImportDraft::LIFETIME_MINUTES),
        ]);

        // Serverseitig erzeugte Dateinamen; kein Name aus dem Formular landet im Pfad.
        foreach (['topics', 'signatures'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $name = 'klassifikation-import/'.Str::ulid().'-'.$field.'.json';
            Storage::disk(ClassificationImportDraft::DISK)->put($name, (string) file_get_contents((string) $file->getRealPath()));
            $draft->{$field.'_path'} = $name;
            $draft->{$field.'_sha256'} = hash_file('sha256', Storage::disk(ClassificationImportDraft::DISK)->path($name));
        }

        $draft->save();

        return redirect()->route('administration.classification-import.show', ['draftId' => $draft->getKey()]);
    }

    /** Vorschau eines Entwurfs: wird bei jedem Aufruf frisch gegen den Bestand gerechnet. */
    public function show(Request $request, string $draftId, ClassificationImportPlanner $planner): Response|RedirectResponse
    {
        $draft = $this->draft($request, $draftId);

        if (! $draft->isUsable() || ! $draft->filesIntact()) {
            return redirect()->route('administration.classification-import.index')->with('import_error', 'Dieser Importentwurf ist abgelaufen oder bereits übernommen. Bitte lade die Dateien erneut hoch.');
        }

        $plan = $planner->plan($draft->absolutePath($draft->topics_path), $draft->absolutePath($draft->signatures_path));

        return response()
            ->view('pages.surfaces.administration.classification-import.preview', [
                'draft' => $draft,
                'plan' => $plan,
                'topicNames' => collect($plan->topics)->pluck('name', 'legacy_id'),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Schritt 2: genau den geprüften Entwurf übernehmen. */
    public function apply(Request $request, string $draftId, ClassificationImportPlanner $planner, ClassificationImporter $importer): RedirectResponse
    {
        $draft = $this->draft($request, $draftId);
        $back = redirect()->route('administration.classification-import.show', ['draftId' => $draft->getKey()]);

        $data = $request->validate([
            'fingerprint' => ['required', 'string', 'size:64'],
            'confirm' => ['accepted'],
            'update_existing' => ['nullable', Rule::in(['0', '1'])],
        ], ['confirm.accepted' => 'Bitte bestätige den Import ausdrücklich.']);

        if (! $draft->isUsable() || ! $draft->filesIntact()) {
            return redirect()->route('administration.classification-import.index')->with('import_error', 'Dieser Importentwurf ist abgelaufen, bereits übernommen oder die Dateien wurden verändert. Bitte lade die Dateien erneut hoch.');
        }

        // Erneute Prüfung unmittelbar vor dem Schreiben: Dieselben Dateien und derselbe Stand des Bestands wie in der Vorschau.
        $plan = $planner->plan($draft->absolutePath($draft->topics_path), $draft->absolutePath($draft->signatures_path));

        if (! hash_equals($plan->fingerprint, $data['fingerprint'])) {
            return $back->with('import_error', 'Der Bestand oder die Dateien haben sich seit der Vorschau geändert. Bitte prüfe die aktualisierte Vorschau und bestätige erneut.');
        }

        if ($plan->errors !== []) {
            return $back->with('import_error', 'Der Import ist nicht möglich, solange es Fehler gibt.');
        }

        $result = $importer->apply($plan, ($data['update_existing'] ?? '0') === '1', [
            'topics' => $draft->topics_sha256,
            'signatures' => $draft->signatures_sha256,
        ]);

        $draft->forceFill(['consumed_at' => now()])->save();
        $draft->discardFiles();

        return redirect()->route('administration.classification-import.index')
            ->with('import_success', 'Der Import ist abgeschlossen.')
            ->with('import_result', $result + ['warnings' => count($plan->warnings)]);
    }

    public function discard(Request $request, string $draftId): RedirectResponse
    {
        $draft = $this->draft($request, $draftId);
        $draft->discardFiles();
        $draft->delete();

        return redirect()->route('administration.classification-import.index')->with('import_success', 'Der Importentwurf wurde verworfen.');
    }

    private function draft(Request $request, string $draftId): ClassificationImportDraft
    {
        return ClassificationImportDraft::query()->where('user_id', $request->user()->getKey())->findOrFail($draftId);
    }

    /** Räumt abgelaufene und übernommene Entwürfe samt Dateien weg. */
    private function pruneExpired(): void
    {
        ClassificationImportDraft::query()
            ->where(static fn ($query) => $query->where('expires_at', '<', now())->orWhereNotNull('consumed_at'))
            ->get()
            ->each(static function (ClassificationImportDraft $old): void {
                $old->discardFiles();
                $old->delete();
            });
    }
}
