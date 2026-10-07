<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Models\Patron;
use App\Modules\Privacy\Services\AnonymizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/** Löschverlangen: ein ausgeschiedenes Ausleihkonto sofort anonymisieren (Name, Geburtstag, Mail, Onlinekonto, Ausweis). */
final class PatronAnonymizeController
{
    public function store(Request $request, string $patronId, AnonymizationService $anonymization): RedirectResponse
    {
        $request->validate(['confirm_erase' => ['accepted']], ['confirm_erase.accepted' => 'Bitte bestätige, dass die Daten unwiderruflich anonymisiert werden sollen.']);

        $patron = Patron::query()->findOrFail($patronId);

        try {
            $anonymization->anonymizePatron($patron);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('pos.patrons.show', ['patronId' => $patron->getKey()])->with('workspace_error', $exception->getMessage());
        }

        return redirect()->route('pos.patrons.show', ['patronId' => $patron->getKey()])->with('workspace_success', 'Das Ausleihkonto wurde anonymisiert. Die Daten der Person sind nicht mehr wiederherstellbar; Statistik und Ausleihzahlen bleiben ohne Personenbezug erhalten.');
    }
}
