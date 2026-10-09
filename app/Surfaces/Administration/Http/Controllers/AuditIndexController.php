<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Support\CsvExport;
use App\Models\User;
use App\Modules\Audit\DTOs\AuditFilter;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Queries\ListAuditEventsQuery;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Models\Patron;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AuditIndexController
{
    private const EXPORT_LIMIT = 20000;

    public function __invoke(Request $request, ListAuditEventsQuery $events): Response
    {
        $filter = $this->filter($request);
        $page = $events->paginate($filter);

        return response()
            ->view('pages.surfaces.administration.audit.index', [
                'events' => $page,
                'areas' => $events->areas(),
                'actions' => $events->actions($filter->area),
                'actors' => $events->actors(),
                'patrons' => $this->patronLabels($page->getCollection()),
                'area' => (string) $filter->area,
                'action' => (string) $filter->action,
                'term' => (string) $filter->term,
                'from' => $filter->from?->toDateString() ?? '',
                'to' => $filter->to?->toDateString() ?? '',
                'actorId' => $filter->actorId,
                'person' => trim((string) $request->query('person', '')),
                'personFound' => $filter->patronIds === null ? null : count($filter->patronIds),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Das gefilterte Protokoll als CSV (zum Beispiel als Nachweis einer Löschung). Der Export selbst wird protokolliert. */
    public function export(Request $request, ListAuditEventsQuery $events, AuditRecorder $audit): StreamedResponse
    {
        $filter = $this->filter($request);
        $rows = $events->query($filter)->limit(self::EXPORT_LIMIT)->get();
        $patrons = $this->patronLabels($rows);
        $actorNames = User::query()->whereIn('id', $rows->pluck('actor_user_id')->filter()->unique()->all())->pluck('name', 'id')->all();

        $audit->record('audit.exported', $rows->count().' Protokolleinträge als CSV exportiert.', null, ['count' => $rows->count(), 'filter' => (string) json_encode(array_filter($request->query()), JSON_UNESCAPED_UNICODE)], (int) $request->user()?->getAuthIdentifier());

        return response()->streamDownload(static function () use ($rows, $patrons, $actorNames): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            CsvExport::put($out, ['Zeitpunkt', 'Ereignis', 'Beschreibung', 'Betroffener Datensatz', 'Person', 'Durch'], ';');

            foreach ($rows as $event) {
                $patronId = $event->subject_id !== null && isset($patrons[(string) $event->subject_id]) ? (string) $event->subject_id : (string) ($event->context['patron_id'] ?? '');

                CsvExport::put($out, [
                    $event->occurred_at->setTimezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y H:i:s'),
                    $event->action,
                    $event->summary,
                    trim(($event->subject_type ? class_basename($event->subject_type) : '').' '.$event->subject_id),
                    $patrons[$patronId] ?? '',
                    $event->actor_user_id === null ? 'System' : ($actorNames[$event->actor_user_id] ?? 'Konto '.$event->actor_user_id),
                ], ';');
            }

            fclose($out);
        }, 'protokoll-'.now()->format('Y-m-d_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filter(Request $request): AuditFilter
    {
        $string = static fn (string $key): ?string => is_string($request->query($key)) && trim((string) $request->query($key)) !== '' ? trim((string) $request->query($key)) : null;
        $date = static function (?string $value): ?CarbonImmutable {
            try {
                return $value !== null ? CarbonImmutable::parse($value) : null;
            } catch (Throwable) {
                return null;
            }
        };

        $person = $string('person');
        $actor = $string('von_konto');

        return new AuditFilter(
            area: $string('bereich'),
            action: $string('ereignis'),
            term: $string('q'),
            from: $date($string('von')),
            to: $date($string('bis')),
            actorId: $actor !== null && ctype_digit($actor) ? (int) $actor : null,
            patronIds: $person !== null ? $this->findPatrons($person) : null,
        );
    }

    /**
     * Ausleihkonten zu Name oder Bibliotheksnummer („Mia“, „Müller“, „Mia Müller“, „123456“).
     *
     * @return list<string>
     */
    private function findPatrons(string $term): array
    {
        $words = array_values(array_filter(preg_split('/\s+/', $term) ?: [], static fn (string $word): bool => $word !== ''));

        return Patron::query()
            ->where(static function ($query) use ($words, $term): void {
                $query->where('library_number', $term);

                $query->orWhere(static function ($names) use ($words): void {
                    foreach ($words as $word) {
                        $like = '%'.$word.'%';
                        $names->where(static fn ($one) => $one->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like));
                    }
                });
            })
            ->limit(100)
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();
    }

    /**
     * Name und Nummer der Personen, auf die sich die Ereignisse beziehen (aus den Konten aufgelöst, das Protokoll speichert nur Kennungen).
     *
     * @param  Collection<int, AuditEvent>  $events
     * @return array<string, string>
     */
    private function patronLabels(Collection $events): array
    {
        $ids = $events->flatMap(static fn (AuditEvent $event): array => array_filter([(string) $event->subject_id, (string) ($event->context['patron_id'] ?? '')]))->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return Patron::query()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name', 'library_number'])
            ->mapWithKeys(static fn (Patron $patron): array => [(string) $patron->getKey() => $patron->last_name.', '.$patron->first_name.' ('.$patron->library_number.')'])
            ->all();
    }
}
