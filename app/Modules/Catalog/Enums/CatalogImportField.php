<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum CatalogImportField: string
{
    case PreferredTitle = 'preferred_title';
    case Subtitle = 'subtitle';
    case SortTitle = 'sort_title';
    case EditionStatement = 'edition_statement';
    case Isbn = 'isbn';
    case PublisherName = 'publisher_name';
    case PublicationYear = 'publication_year';
    case MediaType = 'media_type';
    case LanguageCode = 'language_code';
    case MinimumAge = 'minimum_age';
    case AgeRatingLabel = 'age_rating_label';
    case ContributorName = 'contributor_name';
    case ContributorSortName = 'contributor_sort_name';
    case ContributorRole = 'contributor_role';
    case Barcode = 'barcode';
    case ShelfLocation = 'shelf_location';
    case CopyStatus = 'copy_status';

    /** @return list<string> */
    public static function keys(): array
    {
        return array_map(
            static fn (self $field): string => $field->value,
            self::cases(),
        );
    }
}
