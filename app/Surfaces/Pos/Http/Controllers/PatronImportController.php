<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Patrons\Actions\ImportPatronsAction;
use App\Modules\Patrons\Import\PatronCsvParser;
use App\Modules\Patrons\Import\PatronImportPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PatronImportController
{
    private const DIRECTORY = 'patron-imports';

    public function create(): Response
    {
        return response()
            ->view('pages.surfaces.pos.patrons.import-create', ['columns' => PatronCsvParser::COLUMNS])
            ->header('Cache-Control', 'private, no-store');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(static function (): void {
            echo "\xEF\xBB\xBF";
            echo "vorname;nachname;geburtsdatum;klasse;art;email;bibliotheksnummer\r\n";
            echo "Mia;Beispiel;14.03.2014;5a;Schüler:in;;\r\n";
            echo "Jonas;Muster;2012-11-02;6b;Schüler:in;;\r\n";
            echo "Anna;Lehrerin;21.05.1985;;Lehrkraft;anna@example.invalid;\r\n";
        }, 'ausleihkonten-vorlage.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:2048', 'mimes:csv,txt']], [
            'file.required' => 'Bitte eine CSV-Datei auswählen.',
            'file.max' => 'Die Datei darf höchstens 2 MB groß sein.',
            'file.mimes' => 'Bitte eine CSV-Datei hochladen.',
        ]);

        $token = Str::lower((string) Str::ulid());
        $request->file('file')?->storeAs(self::DIRECTORY, $token.'.csv', 'local');

        return redirect()->route('pos.patrons.import.show', ['token' => $token]);
    }

    public function show(string $token, PatronCsvParser $parser, PatronImportPlanner $planner): Response|RedirectResponse
    {
        try {
            $rows = $parser->parse($this->path($token));
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('pos.patrons.import.create')->withErrors(['file' => $exception->getMessage()]);
        }

        return response()
            ->view('pages.surfaces.pos.patrons.import-preview', [
                'token' => $token,
                'plan' => $planner->plan($rows),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function commit(Request $request, string $token, PatronCsvParser $parser, ImportPatronsAction $import): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Bitte bestätige den Import.']);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        try {
            $result = $import->execute($parser->parse($this->path($token)), $actor);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('pos.patrons.import.show', ['token' => $token])->with('workspace_error', $exception->getMessage());
        }

        Storage::disk('local')->delete(self::DIRECTORY.'/'.$token.'.csv');

        return redirect()
            ->route('pos.patrons.index')
            ->with('workspace_success', "Import abgeschlossen: {$result['created']} Ausleihkonten angelegt, {$result['skipped']} übersprungen.");
    }

    private function path(string $token): string
    {
        abort_unless(preg_match('/^[0-9a-z]{26}$/', $token) === 1, 404);

        $relative = self::DIRECTORY.'/'.$token.'.csv';

        abort_unless(Storage::disk('local')->exists($relative), 404);

        return Storage::disk('local')->path($relative);
    }
}
