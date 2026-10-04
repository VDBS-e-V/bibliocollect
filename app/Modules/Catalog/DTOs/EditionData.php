<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class EditionData
{
    public function __construct(
        public ?string $editionStatement,
        public ?string $isbn,
        public ?string $publisherName,
        public ?int $publicationYear,
        public ?string $mediaType,
        public ?string $languageCode,
        public ?int $minimumAge,
        public ?string $ageRatingLabel,
    ) {}
}
