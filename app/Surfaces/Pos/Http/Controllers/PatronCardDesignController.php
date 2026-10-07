<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\DeletePatronCardDesignAction;
use App\Modules\Patrons\Actions\StorePatronCardDesignAction;
use App\Modules\Patrons\Models\PatronCardDesign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Hintergrundmotive für Vorder- und Rückseiten der Ausweise verwalten (Bilder hochladen, ein- und ausschalten, löschen). */
final class PatronCardDesignController
{
    public function index(): Response
    {
        return response()
            ->view('pages.surfaces.pos.labels.cards-designs', [
                'front' => PatronCardDesign::query()->forSide(PatronCardDesign::FRONT)->get(),
                'back' => PatronCardDesign::query()->forSide(PatronCardDesign::BACK)->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, StorePatronCardDesignAction $action): RedirectResponse
    {
        $data = $request->validate([
            'side' => ['required', Rule::in([PatronCardDesign::FRONT, PatronCardDesign::BACK])],
            'name' => ['required', 'string', 'max:80'],
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:8192', 'dimensions:min_width=1000,ratio=85/54'],
        ], [
            'side.required' => 'Bitte wählen, ob das Motiv für die Vorder- oder die Rückseite gilt.',
            'name.required' => 'Bitte dem Motiv einen Namen geben.',
            'image.required' => 'Bitte eine Bilddatei auswählen.',
            'image.mimes' => 'Das Bild muss eine PNG- oder JPG-Datei sein.',
            'image.max' => 'Das Bild darf höchstens 8 MB groß sein.',
            'image.dimensions' => 'Das Bild muss im Seitenverhältnis 85 : 54 vorliegen (zum Beispiel 2008 × 1276 Pixel) und mindestens 1000 Pixel breit sein.',
        ]);

        $action->execute($data['side'], trim($data['name']), $request->file('image'));

        return redirect()->route('pos.labels.cards.designs')->with('status', 'Das Motiv „'.trim($data['name']).'“ ist hochgeladen und aktiv.');
    }

    /** Ein- oder ausschalten: Nur aktive Motive kommen beim Drucken vor. */
    public function toggle(string $designId): RedirectResponse
    {
        $design = PatronCardDesign::query()->findOrFail($designId);
        $design->forceFill(['is_active' => ! $design->is_active])->save();

        return redirect()->route('pos.labels.cards.designs')->with('status', 'Das Motiv „'.$design->name.'“ ist jetzt '.($design->is_active ? 'aktiv' : 'ausgeschaltet').'.');
    }

    public function destroy(string $designId, DeletePatronCardDesignAction $action): RedirectResponse
    {
        $design = PatronCardDesign::query()->findOrFail($designId);
        $action->execute($design);

        return redirect()->route('pos.labels.cards.designs')->with('status', 'Das Motiv „'.$design->name.'“ ist gelöscht.');
    }
}
