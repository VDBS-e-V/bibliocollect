<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\EditionData;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Support\Facades\DB;

final class UpdateEditionAction
{
    public function execute(Edition $edition, EditionData $data): Edition
    {
        return DB::transaction(function () use ($edition, $data): Edition {
            $lockedEdition = Edition::query()
                ->whereKey($edition->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedEdition->forceFill([
                'edition_statement' => $data->editionStatement,
                'isbn' => $data->isbn,
                'publisher_name' => $data->publisherName,
                'publication_year' => $data->publicationYear,
                'media_type' => $data->mediaType,
                'language_code' => $data->languageCode,
                'minimum_age' => $data->minimumAge,
                'age_rating_label' => $data->ageRatingLabel,
            ])->save();

            return $lockedEdition;
        });
    }
}
