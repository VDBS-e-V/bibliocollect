<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\EditionData;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;

final class CreateEditionAction
{
    public function execute(Title $title, EditionData $data): Edition
    {
        /** @var Edition $edition */
        $edition = $title->editions()->create([
            'edition_statement' => $data->editionStatement,
            'isbn' => $data->isbn,
            'publisher_name' => $data->publisherName,
            'publication_year' => $data->publicationYear,
            'media_type' => $data->mediaType,
            'language_code' => $data->languageCode,
            'minimum_age' => $data->minimumAge,
            'age_rating_label' => $data->ageRatingLabel,
        ]);

        return $edition;
    }
}
