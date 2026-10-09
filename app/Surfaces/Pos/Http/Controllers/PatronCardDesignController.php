<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\DeletePatronCardMotifAction;
use App\Modules\Patrons\Actions\StorePatronCardMotifAction;
use App\Modules\Patrons\Models\PatronCardMotif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Motive der Ausweise verwalten: Vorder- und Rückseite gehören zusammen (hochladen, ein- und ausschalten, löschen). */
final class PatronCardDesignController
{
    public function index(): Response
    {
        return response()
            ->view('pages.surfaces.pos.labels.cards-designs', [
                'motifs' => PatronCardMotif::query()->orderBy('sort_order')->orderBy('name')->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, StorePatronCardMotifAction $action): RedirectResponse
    {
        $image = ['required', 'file', 'mimes:png,jpg,jpeg', 'max:8192', 'dimensions:min_width=1000,ratio=85/54'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'front' => $image,
            'back' => $image,
        ], [
            'name.required' => 'Bitte dem Motiv einen Namen geben.',
            'front.required' => 'Bitte ein Bild für die Vorderseite auswählen.',
            'back.required' => 'Bitte ein Bild für die Rückseite auswählen.',
            '*.mimes' => 'Das Bild muss eine PNG- oder JPG-Datei sein.',
            '*.max' => 'Das Bild darf höchstens 8 MB groß sein.',
            '*.dimensions' => 'Das Bild muss im Seitenverhältnis 85 : 54 vorliegen (zum Beispiel 2008 × 1276 Pixel) und mindestens 1000 Pixel breit sein.',
        ]);

        $name = trim($data['name']);
        $action->execute($name, $request->file('front'), $request->file('back'));

        return redirect()->route('pos.labels.cards.designs')->with('status', 'Das Motiv „'.$name.'“ ist hochgeladen und aktiv.');
    }

    /** Verteilung beim Drucken festlegen: normal, mehr von diesem Motiv oder auslassen. Ausgelassene Motive kommen beim Drucken nicht vor. */
    public function distribution(Request $request, string $designId): RedirectResponse
    {
        $data = $request->validate(['verteilung' => ['required', Rule::in(array_keys(PatronCardMotif::DISTRIBUTIONS))]]);
        $motif = PatronCardMotif::query()->findOrFail($designId);

        if ($data['verteilung'] !== 'skip' && ! $motif->isComplete()) {
            return redirect()->route('pos.labels.cards.designs')->withErrors(['motif' => 'Das Motiv „'.$motif->name.'“ ist unvollständig: Es fehlt ein Bild für die Vorder- oder Rückseite. Bitte neu hochladen.']);
        }

        $motif->forceFill(['distribution' => $data['verteilung'], 'is_active' => $data['verteilung'] !== 'skip'])->save();

        return redirect()->route('pos.labels.cards.designs')->with('status', 'Motiv „'.$motif->name.'“: '.$motif->distributionLabel().'.');
    }

    public function destroy(string $designId, DeletePatronCardMotifAction $action): RedirectResponse
    {
        $motif = PatronCardMotif::query()->findOrFail($designId);
        $action->execute($motif);

        return redirect()->route('pos.labels.cards.designs')->with('status', 'Das Motiv „'.$motif->name.'“ ist gelöscht.');
    }
}
