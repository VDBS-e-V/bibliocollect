<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Support;

use App\Modules\Catalog\DTOs\HoldingSummary;
use App\Modules\Circulation\DTOs\CopyAvailability;
use App\Modules\Circulation\DTOs\CopyLoanState;
use Illuminate\Support\Str;

final class PublicCatalogPresenter
{
    public function mediaTypeLabel(?string $mediaType): string
    {
        if ($mediaType === null || trim($mediaType) === '') {
            return 'Medientyp nicht angegeben';
        }

        return match (mb_strtolower(trim($mediaType))) {
            'book' => 'Buch',
            'audiobook' => 'Hörbuch',
            'ebook', 'e-book' => 'E-Book',
            'comic' => 'Comic',
            'manga' => 'Manga',
            'game', 'board_game' => 'Spiel',
            default => Str::headline($mediaType),
        };
    }

    public function languageLabel(?string $languageCode): string
    {
        if ($languageCode === null || trim($languageCode) === '') {
            return 'Sprache nicht angegeben';
        }

        return match (mb_strtolower(trim($languageCode))) {
            'de', 'deu', 'ger' => 'Deutsch',
            'en', 'eng' => 'Englisch',
            'fr', 'fra', 'fre' => 'Französisch',
            'es', 'spa' => 'Spanisch',
            'it', 'ita' => 'Italienisch',
            default => mb_strtoupper(trim($languageCode)),
        };
    }

    public function roleLabel(string $roleKey): string
    {
        return match (mb_strtolower(trim($roleKey))) {
            'author' => 'Autor:in',
            'illustrator' => 'Illustration',
            'translator' => 'Übersetzung',
            'editor' => 'Herausgabe',
            'narrator' => 'Sprecher:in',
            default => Str::headline($roleKey),
        };
    }

    public function holdingLabel(HoldingSummary $summary): string
    {
        if ($summary->activeCopies === 1) {
            return '1 aktives Exemplar';
        }

        if ($summary->activeCopies > 1) {
            return $summary->activeCopies.' aktive Exemplare';
        }

        if ($summary->hasCopies()) {
            return 'Derzeit kein aktives Exemplar';
        }

        return 'Noch kein Exemplarbestand';
    }

    public function holdingVariant(HoldingSummary $summary): string
    {
        return $summary->hasActiveCopies() ? 'success' : 'neutral';
    }

    /** Verfügbarkeit aus offenen Ausleihen; ohne aktive Exemplare bleibt es bei der Bestandsaussage. */
    public function availabilityLabel(HoldingSummary $summary, CopyAvailability $availability): string
    {
        if (! $availability->hasActiveCopies()) {
            return $this->holdingLabel($summary);
        }

        if ($availability->isAvailable()) {
            $available = $availability->availableCopies();

            if ($availability->activeCopies === 1) {
                return 'Verfügbar';
            }

            return $available.' von '.$availability->activeCopies.' Exemplaren verfügbar';
        }

        return $availability->loanedCopies === 0 && $availability->hasHeldCopies()
            ? 'Für Vormerkung zurückgelegt'
            : 'Derzeit ausgeliehen';
    }

    public function availabilityVariant(HoldingSummary $summary, CopyAvailability $availability): string
    {
        if (! $availability->hasActiveCopies()) {
            return $this->holdingVariant($summary);
        }

        return $availability->isAvailable() ? 'success' : 'warning';
    }

    /** Hinweis auf die früheste Rückgabe, nur wenn aktuell kein Exemplar verfügbar ist. */
    public function availabilityHint(CopyAvailability $availability): ?string
    {
        if (! $availability->hasActiveCopies() || $availability->isAvailable()) {
            return null;
        }

        $parts = [];

        if ($availability->earliestDueOn !== null) {
            $parts[] = 'Frühestens zurück am '.$availability->earliestDueOn->format('d.m.Y');
        }

        if ($availability->waitingReservations > 0) {
            $parts[] = $availability->waitingReservations === 1
                ? '1 Vormerkung'
                : $availability->waitingReservations.' Vormerkungen';
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public function copyStateLabel(CopyLoanState $state): string
    {
        if ($state->loaned) {
            return $state->dueOn !== null
                ? 'Ausgeliehen bis '.$state->dueOn->format('d.m.Y')
                : 'Ausgeliehen';
        }

        return $state->held ? 'Für Vormerkung zurückgelegt' : 'Verfügbar';
    }

    public function copyStateVariant(CopyLoanState $state): string
    {
        return $state->loaned || $state->held ? 'warning' : 'success';
    }

    /** Zusammenfassung wie „2 Exemplare, 1 verfügbar“ für eine Ausgabe. */
    public function copySummary(CopyAvailability $availability): string
    {
        $copies = $availability->activeCopies === 1 ? '1 Exemplar' : $availability->activeCopies.' Exemplare';

        return $copies.', '.$availability->availableCopies().' verfügbar';
    }
}
