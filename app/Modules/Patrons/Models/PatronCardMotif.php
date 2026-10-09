<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Motiv eines Ausweises: Vorder- und Rückseite gehören zusammen. Eigene Uploads liegen in public/card-designs,
 * die mitgelieferten Motive in public/brand/vdbs/card-defaults.
 *
 * @property string $id
 * @property string $name
 * @property string|null $front_path
 * @property string|null $back_path
 * @property bool $is_active
 * @property string $distribution normal|more|skip
 * @property int $sort_order
 */
final class PatronCardMotif extends Model
{
    use HasUlids;

    public const UPLOAD_DIR = 'card-designs';

    /** Verteilung beim Drucken: Bezeichnung und Gewicht (auslassen = 0). */
    public const DISTRIBUTIONS = ['normal' => 'Normal', 'more' => 'Mehr von diesem Motiv', 'skip' => 'Auslassen'];

    public const WEIGHTS = ['normal' => 1, 'more' => 2, 'skip' => 0];

    public function weight(): int
    {
        return self::WEIGHTS[$this->distribution] ?? 1;
    }

    public function distributionLabel(): string
    {
        return self::DISTRIBUTIONS[$this->distribution] ?? 'Normal';
    }

    /** @var list<string> */
    protected $fillable = ['name', 'front_path', 'back_path', 'is_active', 'distribution', 'sort_order'];

    public function frontUrl(): ?string
    {
        return $this->front_path === null ? null : '/'.ltrim($this->front_path, '/');
    }

    public function backUrl(): ?string
    {
        return $this->back_path === null ? null : '/'.ltrim($this->back_path, '/');
    }

    public function isComplete(): bool
    {
        return $this->front_path !== null && $this->back_path !== null;
    }

    /** @return list<string> Dateinamen der eigenen Uploads (mitgelieferte Bilder zählen nicht). */
    public function uploadFiles(): array
    {
        $uploads = array_filter(
            [$this->front_path, $this->back_path],
            static fn (?string $path): bool => $path !== null && str_starts_with($path, self::UPLOAD_DIR.'/'),
        );

        return array_values(array_map(basename(...), $uploads));
    }

    /**
     * Aktive, vollständige Motive in Anzeigereihenfolge.
     *
     * @param  Builder<PatronCardMotif>  $query
     * @return Builder<PatronCardMotif>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotNull('front_path')->whereNotNull('back_path')->orderBy('sort_order')->orderBy('name');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
