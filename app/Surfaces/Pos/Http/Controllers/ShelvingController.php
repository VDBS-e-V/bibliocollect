<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\ShelveCopyAction;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogShelfOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Medien einsortieren: Neu erfasste Bücher (und alles ohne Standort) liegen auf einem Stapel. Erst das Buch scannen, dann
 * das Regalbrett bestätigen oder scannen; das zuletzt benutzte Brett ist vorgewählt, ebenso das Brett der Signatur des Exemplars.
 * So geht es auch mit dem Handy: Buch fotografieren, bestätigen, nächstes Buch.
 */
final class ShelvingController
{
    private const STACK_LIMIT = 200;

    private const LAST_SHELF = 'shelving.last_shelf';

    public function index(Request $request, CatalogShelfOptions $shelves): Response
    {
        $options = $shelves->forSelect();
        $code = trim((string) $request->query('buch', ''));
        $copy = null;
        $error = null;

        if ($code !== '') {
            $copy = Copy::query()->with(['edition.title', 'signature'])->where('barcode', $code)->first();

            if (! $copy instanceof Copy) {
                $error = 'Zur Inventarnummer „'.$code.'“ gibt es kein Exemplar.';
            }
        }

        return response()
            ->view('pages.surfaces.pos.shelving', [
                'copy' => $copy,
                'error' => $error,
                'scanned' => $code,
                'shelfOptions' => $options,
                'preselected' => $copy instanceof Copy ? $this->preselect($request, $copy, $options) : '',
                'stack' => Copy::query()->with('edition.title')->awaitingShelving()->orderBy('barcode')->limit(self::STACK_LIMIT)->get(),
                'stackTotal' => Copy::query()->awaitingShelving()->count(),
                'recent' => Copy::query()->with('edition.title')->whereNotNull('shelved_at')->orderByDesc('shelved_at')->limit(8)->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function scan(Request $request, CatalogShelfOptions $shelves, ShelveCopyAction $shelve): RedirectResponse
    {
        $data = $request->validate([
            'buch' => ['required', 'string', 'max:80'],
            'regalbrett' => ['nullable', 'string', 'max:40'],
            'regalbrett_code' => ['nullable', 'string', 'max:60'],
        ], ['buch.required' => 'Bitte zuerst das Buch scannen.']);

        $book = trim($data['buch']);
        $copy = Copy::query()->with('edition.title')->where('barcode', $book)->first();

        if (! $copy instanceof Copy) {
            return redirect()->route('pos.shelving')->with('shelving_error', 'Zur Inventarnummer „'.$book.'“ gibt es kein Exemplar.');
        }

        $shelf = $this->resolveShelf((string) ($data['regalbrett_code'] ?? ''), (string) ($data['regalbrett'] ?? ''), $shelves->activeCodes());

        if ($shelf === null) {
            return redirect()->route('pos.shelving', ['buch' => $book])->with('shelving_error', 'Bitte ein Regalbrett aus der Liste wählen oder dessen Etikett scannen.');
        }

        $moved = $copy->shelf_location !== null && $copy->shelf_location !== '' && $copy->shelf_location !== $shelf;
        $previous = $copy->shelf_location;

        $shelve->execute($copy, $shelf);
        $request->session()->put(self::LAST_SHELF, $shelf);

        return redirect()->route('pos.shelving')->with('shelving_notice', '„'.$copy->edition->title->preferred_title.'“ ('.$copy->barcode.') steht jetzt auf '.$shelf.'.'.($moved ? ' Vorher: '.$previous.'.' : ''));
    }

    /**
     * Das Regalbrett aus dem gescannten Etikett (ohne Rücksicht auf Punkte, Leerzeichen und Schreibweise) oder aus der Liste.
     *
     * @param  list<string>  $active
     */
    private function resolveShelf(string $scanned, string $selected, array $active): ?string
    {
        $scanned = trim($scanned);

        if ($scanned !== '') {
            foreach ($active as $code) {
                if ($this->normalize($code) === $this->normalize($scanned)) {
                    return $code;
                }
            }

            return null;
        }

        return in_array(trim($selected), $active, true) ? trim($selected) : null;
    }

    /** @param array<string, string> $options */
    private function preselect(Request $request, Copy $copy, array $options): string
    {
        $wanted = trim((string) $request->query('regalbrett', ''));

        if (isset($options[$wanted])) {
            return $wanted;
        }

        if ($copy->signature_id !== null) {
            $bySignature = CatalogShelf::query()->where('is_active', true)->where('signature_id', $copy->signature_id)->value('code');

            if (is_string($bySignature) && isset($options[$bySignature])) {
                return $bySignature;
            }
        }

        // Andere Exemplare derselben Ausgabe stehen schon im Regal: dort gehört dieses auch hin.
        $sibling = Copy::query()
            ->where('edition_id', $copy->edition_id)
            ->whereKeyNot($copy->getKey())
            ->whereNotNull('shelf_location')
            ->where('shelf_location', '!=', '')
            ->orderByDesc('shelved_at')
            ->value('shelf_location');

        if (is_string($sibling) && isset($options[$sibling])) {
            return $sibling;
        }

        $last = $request->session()->get(self::LAST_SHELF);

        return is_string($last) && isset($options[$last]) ? $last : '';
    }

    private function normalize(string $value): string
    {
        return mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]/u', '', $value));
    }
}
