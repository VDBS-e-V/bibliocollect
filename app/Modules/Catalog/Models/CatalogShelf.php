<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Regalbrett. Der Code ist der Standort, der am Exemplar steht; die Beschriftung nennt, was dort steht.
 *
 * @property string $id
 * @property string $code
 * @property string|null $label
 * @property string|null $signature_id
 * @property string|null $section_id
 * @property string|null $board
 * @property int|null $capacity
 * @property int $sort_order
 * @property bool $is_active
 */
final class CatalogShelf extends Model
{
    use HasUlids;

    protected $table = 'catalog_shelves';

    /** @var list<string> */
    protected $fillable = ['code', 'label', 'signature_id', 'section_id', 'board', 'capacity', 'sort_order', 'is_active'];

    /**
     * Das Regal, in dem das Regalbrett liegt.
     *
     * @return BelongsTo<CatalogShelfSection, $this>
     */
    public function rack(): BelongsTo
    {
        return $this->belongsTo(CatalogShelfSection::class, 'section_id');
    }

    /** @return BelongsToMany<CatalogTopic, $this> */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(CatalogTopic::class, 'catalog_shelf_topics', 'shelf_id', 'topic_id')->withPivot('position')->orderByPivot('position');
    }

    /**
     * Nur noch für den Import aus dem Altsystem.
     *
     * @return BelongsTo<CatalogSignature, $this>
     */
    public function signature(): BelongsTo
    {
        return $this->belongsTo(CatalogSignature::class, 'signature_id');
    }

    public function display(): string
    {
        return $this->label !== null && $this->label !== '' ? $this->code.' · '.$this->label : $this->code;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer', 'capacity' => 'integer'];
    }
}
