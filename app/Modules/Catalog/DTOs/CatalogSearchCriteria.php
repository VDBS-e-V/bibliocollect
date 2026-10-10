<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class CatalogSearchCriteria
{
    public function __construct(
        public ?string $term = null,
        public ?string $title = null,
        public ?string $contributor = null,
        public ?string $subject = null,
        public ?string $identifier = null,
        public ?string $publisher = null,
        public ?string $publicationPlace = null,
        public ?string $series = null,
        public ?string $topic = null,
        public ?string $shelf = null,
        public ?string $theme = null,
        public ?string $classification = null,
        public ?string $targetAudience = null,
        public ?string $sourceRecordId = null,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?string $mediaType = null,
        public ?string $languageCode = null,
        /** Altersstufe als „von-bis“ des empfohlenen Mindestalters, zum Beispiel „7-10“. */
        public ?string $ageStage = null,
        public bool $activeCopiesOnly = false,
        public bool $availableNowOnly = false,
        public string $sort = 'title',
        public int $perPage = 20,
        public int $page = 1,
        /** Öffentlicher Katalog: nur Titel mit mindestens einem vorhandenen Exemplar (nicht ausgesondert, nicht verloren). */
        public bool $presentCopiesOnly = false,
    ) {}

    public function hasSearchInput(): bool
    {
        return $this->term !== null
            || $this->title !== null
            || $this->contributor !== null
            || $this->subject !== null
            || $this->identifier !== null
            || $this->publisher !== null
            || $this->publicationPlace !== null
            || $this->series !== null
            || $this->topic !== null
            || $this->shelf !== null
            || $this->theme !== null
            || $this->classification !== null
            || $this->targetAudience !== null
            || $this->sourceRecordId !== null
            || $this->yearFrom !== null
            || $this->yearTo !== null
            || $this->mediaType !== null
            || $this->languageCode !== null
            || $this->ageStage !== null
            || $this->activeCopiesOnly
            || $this->availableNowOnly;
    }

    public function hasAdvancedFilters(): bool
    {
        return $this->title !== null
            || $this->contributor !== null
            || $this->subject !== null
            || $this->identifier !== null
            || $this->publisher !== null
            || $this->publicationPlace !== null
            || $this->series !== null
            || $this->topic !== null
            || $this->classification !== null
            || $this->targetAudience !== null
            || $this->sourceRecordId !== null
            || $this->yearFrom !== null
            || $this->yearTo !== null;
    }
}
