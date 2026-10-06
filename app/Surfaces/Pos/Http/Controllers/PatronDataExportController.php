<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Queries\FindPatronQuery;
use App\Modules\Privacy\Services\PatronDataExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Auskunft über alle zu einem Ausleihkonto gespeicherten Daten, als Ansicht zum Ausdrucken und als JSON. */
final class PatronDataExportController
{
    public function show(string $patronId, FindPatronQuery $findPatron, PatronDataExport $export, AuditRecorder $audit): Response
    {
        $patron = $findPatron->byId($patronId);
        $audit->record('privacy.export.viewed', 'Auskunft über gespeicherte Daten eines Ausleihkontos angezeigt.', $patron);

        return response()
            ->view('pages.surfaces.pos.patrons.data-export', ['patron' => $patron, 'data' => $export->export($patron)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request, string $patronId, FindPatronQuery $findPatron, PatronDataExport $export, AuditRecorder $audit): StreamedResponse
    {
        $patron = $findPatron->byId($patronId);
        $audit->record('privacy.export.downloaded', 'Auskunft über gespeicherte Daten eines Ausleihkontos heruntergeladen.', $patron);

        return response()->streamDownload(static function () use ($export, $patron): void {
            echo json_encode($export->export($patron), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, 'auskunft-'.$patron->library_number.'.json', ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
