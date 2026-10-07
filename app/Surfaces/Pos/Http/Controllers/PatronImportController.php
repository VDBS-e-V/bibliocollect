<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Patrons\Actions\ImportPatronsAction;
use App\Modules\Patrons\Import\PatronCsvParser;
use App\Modules\Patrons\Import\PatronImportPlanner;
use App\Modules\School\Queries\ListAssignableSchoolClassesQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PatronImportController
{
    private const DIRECTORY = 'patron-imports';

    public function create(ListAssignableSchoolClassesQuery $schoolClasses): Response
    {
        return response()
            ->view('pages.surfaces.pos.patrons.import-create', [
                'columns' => PatronCsvParser::COLUMNS,
                'schoolClasses' => $schoolClasses->execute(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(static function (): void {
            echo "\xEF\xBB\xBF";
            echo "vorname;nachname;geburtsdatum;email\r\n";
            echo "Mia;Beispiel;14.03.2014;\r\n";
            echo "Jonas;Muster;2012-11-02;jonas@example.invalid\r\n";
        }, 'klassenliste-vorlage.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, ListAssignableSchoolClassesQuery $schoolClasses): RedirectResponse
    {
        $request->validate([
            'school_class_id' => ['required', 'string', Rule::in($schoolClasses->execute()->map(static fn ($class): string => (string) $class->getKey())->all())],
            'file' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
        ], [
            'school_class_id.required' => 'Bitte wähle die Klasse, für die du importierst.',
            'school_class_id.in' => 'Diese Klasse gibt es im aktiven Schuljahr nicht.',
            'file.required' => 'Bitte eine CSV-Datei auswählen.',
            'file.max' => 'Die Datei darf höchstens 2 MB groß sein.',
            'file.mimes' => 'Bitte eine CSV-Datei hochladen.',
        ]);

        $token = Str::lower((string) Str::ulid());
        $request->file('file')?->storeAs(self::DIRECTORY, $token.'.csv', 'local');
        Storage::disk('local')->put(self::DIRECTORY.'/'.$token.'.json', json_encode(['class_id' => (string) $request->input('school_class_id')], JSON_THROW_ON_ERROR));

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
                'plan' => $planner->plan($rows, $this->classId($token)),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function commit(Request $request, string $token, PatronCsvParser $parser, ImportPatronsAction $import): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Bitte bestätige den Import.']);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        try {
            $result = $import->execute($parser->parse($this->path($token)), $this->classId($token), $actor);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('pos.patrons.import.show', ['token' => $token])->with('workspace_error', $exception->getMessage());
        }

        Storage::disk('local')->delete([self::DIRECTORY.'/'.$token.'.csv', self::DIRECTORY.'/'.$token.'.json']);

        return redirect()
            ->route('pos.patrons.index')
            ->with('workspace_success', "Import abgeschlossen: {$result['created']} Ausleihkonten angelegt, {$result['skipped']} übersprungen. Die Ausweise gibst du unter „Ausweise klassenweise ausgeben“ aus, sobald die Schüler:innen da sind.");
    }

    private function classId(string $token): string
    {
        $this->path($token);

        $meta = json_decode((string) Storage::disk('local')->get(self::DIRECTORY.'/'.$token.'.json'), true);

        return is_array($meta) && is_string($meta['class_id'] ?? null) ? $meta['class_id'] : '';
    }

    private function path(string $token): string
    {
        abort_unless(preg_match('/^[0-9a-z]{26}$/', $token) === 1, 404);

        $relative = self::DIRECTORY.'/'.$token.'.csv';

        abort_unless(Storage::disk('local')->exists($relative), 404);

        return Storage::disk('local')->path($relative);
    }
}
