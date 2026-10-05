<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Support;

use App\Modules\Catalog\DTOs\HoldingSummary;
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
}
