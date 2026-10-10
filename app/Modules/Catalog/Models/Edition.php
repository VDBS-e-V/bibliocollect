<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Services\SeriesService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $title_id
 * @property string|null $edition_statement
 * @property string|null $isbn
 * @property string|null $publisher_name
 * @property int|null $publication_year
 * @property string|null $media_type
 * @property string|null $language_code
 * @property int|null $minimum_age
 * @property string|null $age_rating_label
 * @property string|null $responsibility_statement
 * @property string|null $series_statement
 * @property string|null $series_id
 * @property string|null $series_volume
 * @property-read Series|null $series
 * @property string|null $publication_place
 * @property string|null $edition_number
 * @property array<int, string>|null $alternate_identifiers
 * @property string|null $issn
 * @property string|null $doi_handle
 * @property string|null $local_classification
 * @property string|null $original_language_code
 * @property int|null $page_count
 * @property string|null $physical_extent
 * @property int|null $file_size_bytes
 * @property string|null $format_type
 * @property string|null $summary
 * @property string|null $subject_keywords
 * @property string|null $subject_keywords_system
 * @property string|null $target_audience
 * @property array<string|int, mixed>|null $age_recommendation
 * @property string|null $metadata_source
 * @property string|null $source_record_id
 * @property string|null $source_permalink
 * @property string|null $legacy_source
 * @property string|null $legacy_record_key
 */
final class Edition extends Model
{
    use HasUlids;

    protected $table = 'catalog_editions';

    /** @var list<string> */
    protected $fillable = [
        'title_id',
        'edition_statement',
        'isbn',
        'publisher_name',
        'publication_year',
        'media_type',
        'language_code',
        'minimum_age',
        'age_rating_label',
        'responsibility_statement',
        'series_statement',
        'series_id',
        'series_volume',
        'publication_place',
        'edition_number',
        'alternate_identifiers',
        'issn',
        'doi_handle',
        'local_classification',
        'original_language_code',
        'page_count',
        'physical_extent',
        'file_size_bytes',
        'format_type',
        'summary',
        'subject_keywords',
        'subject_keywords_system',
        'target_audience',
        'age_recommendation',
        'metadata_source',
        'source_record_id',
        'source_permalink',
        'legacy_source',
        'legacy_record_key',
    ];

    protected static function booted(): void
    {
        // Reihe und Bandnummer aus der Reihenangabe ableiten, sobald sie sich ändert (oder noch nicht zugeordnet ist).
        self::saving(static function (self $edition): void {
            if ($edition->isDirty('series_statement') || ($edition->series_id === null && trim((string) $edition->series_statement) !== '')) {
                app(SeriesService::class)->assign($edition);
            }
        });
    }

    /** @return BelongsTo<Series, $this> */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }

    /** @return BelongsTo<Title, $this> */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id');
    }

    /** @return HasMany<Copy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class, 'edition_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'minimum_age' => 'integer',
            'page_count' => 'integer',
            'file_size_bytes' => 'integer',
            'alternate_identifiers' => 'array',
            'age_recommendation' => 'array',
        ];
    }
}
