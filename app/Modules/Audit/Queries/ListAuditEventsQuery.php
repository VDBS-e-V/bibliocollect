<?php

declare(strict_types=1);

namespace App\Modules\Audit\Queries;

use App\Models\User;
use App\Modules\Audit\DTOs\AuditFilter;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListAuditEventsQuery
{
    /** @return LengthAwarePaginator<int, AuditEvent> */
    public function paginate(AuditFilter $filter, int $perPage = 50): LengthAwarePaginator
    {
        return $this->query($filter)->paginate($perPage)->withQueryString();
    }

    /** @return Builder<AuditEvent> */
    public function query(AuditFilter $filter): Builder
    {
        return AuditEvent::query()
            ->with('actor')
            ->when($filter->area !== null && $filter->area !== '', static fn (Builder $query) => $query->where('action', 'like', $filter->area.'.%'))
            ->when($filter->action !== null && $filter->action !== '', static fn (Builder $query) => $query->where('action', $filter->action))
            ->when($filter->term !== null && trim($filter->term) !== '', static function (Builder $query) use ($filter): void {
                $like = '%'.trim((string) $filter->term).'%';
                $query->where(static function (Builder $inner) use ($like): void {
                    $inner->where('summary', 'like', $like)->orWhere('subject_id', 'like', $like)->orWhere('action', 'like', $like);
                });
            })
            ->when($filter->from !== null, static fn (Builder $query) => $query->where('occurred_at', '>=', $filter->from?->startOfDay()))
            ->when($filter->to !== null, static fn (Builder $query) => $query->where('occurred_at', '<=', $filter->to?->endOfDay()))
            ->when($filter->actorId !== null, static fn (Builder $query) => $query->where('actor_user_id', $filter->actorId))
            ->when($filter->patronIds !== null, static function (Builder $query) use ($filter): void {
                $ids = $filter->patronIds ?? [];

                if ($ids === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(static function (Builder $inner) use ($ids): void {
                    $inner->whereIn('subject_id', $ids)->orWhereIn('context->patron_id', $ids);
                });
            })
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
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

    /** @return list<string> Ereignisarten, optional nur eines Bereichs */
    public function actions(?string $area = null): array
    {
        return AuditEvent::query()
            ->when($area !== null && $area !== '', static fn (Builder $query) => $query->where('action', 'like', $area.'.%'))
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(static fn (mixed $action): string => (string) $action)
            ->all();
    }

    /** @return array<int, string> Konten, die Ereignisse ausgelöst haben (Kennung zu Name) */
    public function actors(): array
    {
        $ids = AuditEvent::query()->whereNotNull('actor_user_id')->distinct()->pluck('actor_user_id')->all();

        return User::query()->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id')->all();
    }
}
