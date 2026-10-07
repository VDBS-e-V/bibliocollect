<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\MarkCopyFoundAction;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use InvalidArgumentException;

/** Wieder aufgetauchte oder reparierte Exemplare am Ausleihplatz wieder verfügbar machen (kein Katalogrecht nötig). */
final class CopyFoundController
{
    public function show(string $barcode): Response|RedirectResponse
    {
        $copy = Copy::query()->with('edition.title')->where('barcode', $barcode)->firstOrFail();

        if (! in_array($copy->status, [CopyStatus::Lost, CopyStatus::Damaged], true)) {
            return redirect()->route('pos.home')->with('workspace_error', "Das Exemplar {$copy->barcode} ist nicht als verloren oder beschädigt eingetragen.");
        }

        return response()
            ->view('pages.surfaces.pos.copy-found', ['copy' => $copy])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(string $barcode, MarkCopyFoundAction $found): RedirectResponse
    {
        $copy = Copy::query()->where('barcode', $barcode)->firstOrFail();

        try {
            $found->execute($copy);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('pos.home')->with('workspace_error', $exception->getMessage());
        }

        $held = Reservation::query()->where('ready_copy_id', $copy->getKey())->where('status', ReservationStatus::Ready->value)->exists();

        return redirect()->route('pos.home')->with('workspace_success', "Das Exemplar {$copy->barcode} ist wieder verfügbar.".($held ? ' Es liegt jetzt für eine Vormerkung bereit: bitte zur Abholung zurücklegen.' : ' Es kann zurück ins Regal.'));
    }
}
