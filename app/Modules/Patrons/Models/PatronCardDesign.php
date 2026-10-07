<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Hintergrundmotiv für eine Ausweisseite. Eigene Uploads liegen in public/card-designs, die mitgelieferten
 * Motive in public/brand/vdbs/card-defaults.
 *
 * @property string $id
 * @property string $side front|back
 * @property string $name
 * @property string $path
 * @property bool $is_active
 * @property int $sort_order
 */
final class PatronCardDesign extends Model
{
    use HasUlids;

    public const FRONT = 'front';

    public const BACK = 'back';

    public const UPLOAD_DIR = 'card-designs';

    /** @var list<string> */
    protected $fillable = ['side', 'name', 'path', 'is_active', 'sort_order'];

    public function url(): string
    {
        return '/'.ltrim($this->path, '/');
    }

    public function isUpload(): bool
    {
        return str_starts_with($this->path, self::UPLOAD_DIR.'/');
    }

    /**
     * @param  Builder<PatronCardDesign>  $query
     * @return Builder<PatronCardDesign>
     */
    public function scopeForSide(Builder $query, string $side): Builder
    {
        return $query->where('side', $side)->orderBy('sort_order')->orderBy('name');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
