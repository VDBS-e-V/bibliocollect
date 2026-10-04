<?php

declare(strict_types=1);

namespace App\Modules\School\DTOs;

use App\Modules\School\Models\SchoolYear;
use Illuminate\Database\Eloquent\Collection;

final readonly class SchoolYearTransitionReadiness
{
    /**
     * @param  Collection<int, SchoolYear>  $candidateYears
     * @param  list<int>  $missingPromotedGradeLevels
     */
    public function __construct(
        public ?SchoolYear $activeYear,
        public Collection $candidateYears,
        public ?SchoolYear $targetYear,
        public int $targetActiveClassCount,
        public array $missingPromotedGradeLevels,
    ) {}

    public function isReady(): bool
    {
        return $this->targetYear !== null
            && $this->targetActiveClassCount > 0
            && $this->missingPromotedGradeLevels === [];
    }
}
