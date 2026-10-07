<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\ShelveCopyAction;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogShelfOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Medien einsortieren: Neu erfasste Bücher liegen auf einem Stapel. Wer sie ins Regal stellt, wählt das Regalbrett und
 * scannt die Bücher; damit wird der Standort im System vermerkt und das Buch vom Stapel genommen.
 */
final class ShelvingController
{
    private const STACK_LIMIT = 200;

    public function index(Request $request, CatalogShelfOptions $shelves): Response
    {
        $shelf = trim((string) $request->query('regalbrett', ''));
        $options = $shelves->forSelect();

        return response()
            ->view('pages.surfaces.pos.shelving', [
                'shelf' => isset($options[$shelf]) ? $shelf : '',
                'shelfOptions' => $options,
                'stack' => Copy::query()->with('edition.title')->where('needs_shelving', true)->orderBy('created_at')->orderBy('barcode')->limit(self::STACK_LIMIT)->get(),
                'stackTotal' => Copy::query()->where('needs_shelving', true)->count(),
                'recent' => Copy::query()->with('edition.title')->whereNotNull('shelved_at')->orderByDesc('shelved_at')->limit(8)->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function scan(Request $request, CatalogShelfOptions $shelves, ShelveCopyAction $shelve): RedirectResponse
    {
        $data = $request->validate([
            'regalbrett' => ['required', 'string', 'max:40'],
            'code' => ['required', 'string', 'max:80'],
        ], [
            'regalbrett.required' => 'Bitte zuerst das Regalbrett wählen.',
            'code.required' => 'Bitte die Inventarnummer des Buchs scannen.',
        ]);

        $shelf = trim($data['regalbrett']);
        $back = ['regalbrett' => $shelf];

        if (! in_array($shelf, $shelves->activeCodes(), true)) {
            return redirect()->route('pos.shelving')->with('shelving_error', 'Dieses Regalbrett gibt es nicht (mehr). Bitte wähle eines aus der Liste.');
        }

        $copy = Copy::query()->with('edition.title')->where('barcode', trim($data['code']))->first();

        if (! $copy instanceof Copy) {
            return redirect()->route('pos.shelving', $back)->with('shelving_error', 'Zur Inventarnummer „'.trim($data['code']).'“ gibt es kein Exemplar.');
        }

        $moved = ! $copy->needs_shelving && $copy->shelf_location !== null && $copy->shelf_location !== $shelf;
        $previous = $copy->shelf_location;

        $shelve->execute($copy, $shelf);

        return redirect()->route('pos.shelving', $back)->with('shelving_notice', '„'.$copy->edition->title->preferred_title.'“ ('.$copy->barcode.') steht jetzt auf '.$shelf.'.'.($moved ? ' Vorher: '.$previous.'.' : ''));
    }
}
