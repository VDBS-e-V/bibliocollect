<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\AssignPatronCardAction;
use App\Modules\Patrons\Actions\BlockPatronCardAction;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Exceptions\PatronCardConflict;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Queries\FindPatronQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Ausweise einer Person auf ihrer Kontoseite: neu ausstellen (ersetzt den bisherigen) oder sperren. */
final class PatronAccountCardController
{
    public function assign(Request $request, string $patronId, FindPatronQuery $findPatron, AssignPatronCardAction $assign): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:40'],
            'old_reason' => ['nullable', Rule::in(['replaced', 'lost', 'defective'])],
        ], ['number.required' => 'Bitte den neuen Ausweis scannen oder die Nummer eingeben.']);

        $patron = $findPatron->byId($patronId);
        $number = trim($data['number']);

        try {
            $replaced = $assign->execute($number, $patron, CardBlockReason::from($data['old_reason'] ?? 'replaced'));
        } catch (PatronCardConflict $exception) {
            return redirect()->route('pos.patrons.show', ['patronId' => $patronId])->with('workspace_error', $exception->getMessage());
        }

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patronId])
            ->with('workspace_success', 'Ausweis '.$number.' ist zugeordnet.'.($replaced > 0 ? ' Der bisherige Ausweis wurde gesperrt.' : ''));
    }

    public function block(Request $request, string $patronId, string $cardId, BlockPatronCardAction $block): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', Rule::in(['lost', 'defective', 'withdrawn'])]], ['reason.required' => 'Bitte einen Grund wählen.']);

        $card = PatronCard::query()->where('patron_id', $patronId)->findOrFail($cardId);
        $block->execute($card, CardBlockReason::from($data['reason']));

        return redirect()
            ->route('pos.patrons.show', ['patronId' => $patronId])
            ->with('workspace_success', 'Ausweis '.$card->number.' ist gesperrt.');
    }
}
