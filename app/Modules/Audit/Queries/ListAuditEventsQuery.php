<?php

declare(strict_types=1);

namespace App\Modules\Audit\Queries;

use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAuditEventsQuery
{
    /** @return LengthAwarePaginator<int, AuditEvent> */
    public function paginate(?string $area, ?string $term, int $perPage = 50): LengthAwarePaginator
    {
        return AuditEvent::query()
            ->with('actor')
            ->when($area !== null && $area !== '', static fn ($query) => $query->where('action', 'like', $area.'.%'))
            ->when($term !== null && trim($term) !== '', static function ($query) use ($term): void {
                $like = '%'.trim((string) $term).'%';
                $query->where(static function ($inner) use ($like): void {
                    $inner->where('summary', 'like', $like)->orWhere('subject_id', 'like', $like);
                });
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return list<string> Bereiche, zu denen es Ereignisse gibt (erster Teil der Aktion) */
    public function areas(): array
    {
        return AuditEvent::query()
            ->selectRaw("distinct substr(action, 1, instr(action, '.') - 1) as area")
            ->pluck('area')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }
}
