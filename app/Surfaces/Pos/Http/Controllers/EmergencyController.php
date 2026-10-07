<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\Circulation\Actions\RecordPaperTransactionsAction;
use App\Modules\Circulation\Exceptions\PaperEntryRejected;
use App\Modules\Circulation\Queries\EmergencyListQuery;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Notbetrieb: die Liste der offenen Ausleihen und Vormerkungen zum Ausdrucken und das Nachtragen von Ausleihen und
 * Rückgaben, die bei einem Ausfall auf Papier festgehalten wurden.
 */
final class EmergencyController
{
    public function index(Request $request, BusinessClock $clock, EmergencyListQuery $list): Response
    {
        return response()
            ->view('pages.surfaces.pos.emergency.index', [
                'today' => $clock->now()->toDateString(),
                'earliest' => $clock->now()->startOfDay()->subDays(RecordPaperTransactionsAction::MAX_DAYS_BACK)->toDateString(),
                'loanCount' => count($list->loans()),
                'reservationCount' => count($list->reservations()),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function list(BusinessClock $clock, EmergencyListQuery $list): Response
    {
        return response()
            ->view('pages.surfaces.pos.emergency.list', [
                'loans' => $list->loans(),
                'reservations' => $list->reservations(),
                'now' => $clock->now(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function export(BusinessClock $clock, EmergencyListQuery $list): StreamedResponse
    {
        $loans = $list->loans();
        $reservations = $list->reservations();
        $name = 'notfallliste-'.$clock->now()->format('Ymd-Hi').'.csv';

        return response()->streamDownload(static function () use ($loans, $reservations): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Art', 'Name', 'Klasse', 'Bibliotheksnummer', 'Titel', 'Inventarnummer', 'Ausgeliehen am', 'Fällig am', 'Status', 'Abholen bis'], ';');

            foreach ($loans as $row) {
                fputcsv($out, ['Ausleihe', $row['patron'], $row['class'], $row['library_number'], $row['title'], $row['barcode'], $row['checked_out_on'], $row['due_on'], '', ''], ';');
            }

            foreach ($reservations as $row) {
                fputcsv($out, ['Vormerkung', $row['patron'], $row['class'], $row['library_number'], $row['title'], $row['ready_barcode'], '', '', $row['status'], $row['pickup_until']], ';');
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function storeLoans(Request $request, RecordPaperTransactionsAction $record): RedirectResponse
    {
        $data = $request->validate([
            'person' => ['required', 'string', 'max:60'],
            'date' => ['required', 'string', 'max:10'],
            'barcodes' => ['required', 'string', 'max:5000'],
        ], [
            'person.required' => 'Bitte die Ausweis- oder Bibliotheksnummer der Person eintragen.',
            'date.required' => 'Bitte das Datum der Ausleihe angeben.',
            'barcodes.required' => 'Bitte mindestens eine Inventarnummer eintragen.',
        ]);

        $patron = $this->findPatron($data['person']);

        if (! $patron instanceof Patron) {
            return back()->withInput()->withErrors(['person' => 'Zu „'.trim($data['person']).'“ gibt es keine Person. Gib die Nummer vom Ausweis oder die Bibliotheksnummer ein.'], 'loans');
        }

        try {
            $count = $record->loans($patron, $data['date'], $this->lines($data['barcodes']), $request->user());
        } catch (PaperEntryRejected $exception) {
            return back()->withInput()->withErrors($exception->problems, 'loans');
        }

        return redirect()->route('pos.emergency')->with('emergency_success', "{$count} Ausleihe(n) für {$patron->displayName()} nachgetragen.");
    }

    public function storeReturns(Request $request, RecordPaperTransactionsAction $record): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'string', 'max:10'],
            'barcodes' => ['required', 'string', 'max:5000'],
        ], [
            'date.required' => 'Bitte das Datum der Rückgabe angeben.',
            'barcodes.required' => 'Bitte mindestens eine Inventarnummer eintragen.',
        ]);

        try {
            $count = $record->returns($data['date'], $this->lines($data['barcodes']), $request->user());
        } catch (PaperEntryRejected $exception) {
            return back()->withInput()->withErrors($exception->problems, 'returns');
        }

        return redirect()->route('pos.emergency')->with('emergency_success', "{$count} Rückgabe(n) nachgetragen.");
    }

    /** Ausweisnummer (zugeordnet) oder Bibliotheksnummer. */
    private function findPatron(string $code): ?Patron
    {
        $code = trim($code);

        $card = PatronCard::query()->with('patron')->where('number', $code)->where('status', CardStatus::Assigned->value)->first();

        if ($card instanceof PatronCard && $card->patron instanceof Patron) {
            return $card->patron;
        }

        return Patron::query()->whereRaw('lower(library_number) = ?', [mb_strtolower($code)])->first();
    }

    /** @return list<string> */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $text) ?: []), static fn (string $line): bool => $line !== ''));
    }
}
