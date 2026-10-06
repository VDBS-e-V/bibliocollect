<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Circulation\Queries\ListOpenReservationsQuery;
use Illuminate\Http\Response;

final class ReservationIndexController
{
    public function __invoke(ListOpenReservationsQuery $reservations): Response
    {
        return response()
            ->view('pages.surfaces.pos.reservations.index', [
                'ready' => $reservations->ready(),
                'waiting' => $reservations->waiting()->groupBy('title_id'),
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
