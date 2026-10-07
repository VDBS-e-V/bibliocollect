<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Queries;

use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Patrons\Models\Patron;

/**
 * Die Notfallliste: alle offenen Ausleihen und Vormerkungen auf einen Blick, sortiert nach Name.
 * Gedacht zum Ausdrucken, damit der Betrieb bei einem Ausfall mit Papier weiterlaufen kann.
 */
final class EmergencyListQuery
{
    /**
     * @return list<array{patron: string, class: string, library_number: string, title: string, barcode: string, checked_out_on: string, due_on: string}>
     */
    public function loans(): array
    {
        $loans = Loan::query()
            ->whereNull('returned_at')
            ->with(['patron.schoolClass', 'copy.edition.title'])
            ->get();

        $rows = $loans->map(fn (Loan $loan): array => [
            'patron' => $this->name($loan->patron),
            'class' => $this->className($loan->patron),
            'library_number' => $loan->patron->library_number,
            'title' => $loan->copy->edition->title->preferred_title,
            'barcode' => $loan->copy->barcode,
            'checked_out_on' => $loan->checked_out_at->timezone(config('app.timezone'))->format('d.m.Y'),
            'due_on' => $loan->due_on->format('d.m.Y'),
        ])->all();

        usort($rows, static fn (array $a, array $b): int => strcasecmp($a['patron'], $b['patron']) ?: strcmp($a['barcode'], $b['barcode']));

        return $rows;
    }

    /**
     * @return list<array{patron: string, class: string, library_number: string, title: string, status: string, ready_barcode: string, pickup_until: string}>
     */
    public function reservations(): array
    {
        $reservations = Reservation::query()
            ->whereIn('status', ReservationStatus::openValues())
            ->with(['patron.schoolClass', 'title', 'readyCopy'])
            ->orderBy('requested_at')
            ->get();

        $rows = $reservations->map(fn (Reservation $reservation): array => [
            'patron' => $this->name($reservation->patron),
            'class' => $this->className($reservation->patron),
            'library_number' => $reservation->patron->library_number,
            'title' => $reservation->title->preferred_title,
            'status' => $reservation->status->label(),
            'ready_barcode' => $reservation->ready_copy_id !== null ? $reservation->readyCopy->barcode : '',
            'pickup_until' => $reservation->pickup_until?->format('d.m.Y') ?? '',
        ])->all();

        usort($rows, static fn (array $a, array $b): int => strcasecmp($a['patron'], $b['patron']) ?: strcmp($a['title'], $b['title']));

        return $rows;
    }

    private function className(Patron $patron): string
    {
        return $patron->school_class_id !== null ? $patron->schoolClass->name : '';
    }

    private function name(Patron $patron): string
    {
        return $patron->last_name.', '.$patron->first_name;
    }
}
