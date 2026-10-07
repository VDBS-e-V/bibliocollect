<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Foundation\Support\CsvExport;
use App\Modules\Catalog\Actions\RestoreWithdrawnCopyAction;
use App\Modules\Catalog\Actions\WithdrawCopiesAction;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Enums\WithdrawalFate;
use App\Modules\Catalog\Enums\WithdrawalReason;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Services\CopyWithdrawalCheck;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Aussondern: Inventarnummern eingeben oder scannen, prüfen (nicht ausgeliehen, nicht zurückgelegt), Grund und Verbleib
 * wählen, bestätigen. Dazu die Liste für den Jahresbericht (auch als CSV) und das Zurückholen bei einem Irrtum.
 */
final class WithdrawalController
{
    private const MAX_NUMBERS = 200;

    public function index(Request $request): Response
    {
        return response()
            ->view('pages.surfaces.pos.withdrawal.index', ['numbers' => (string) $request->query('nummern', '')])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Schritt 2: Prüfen, welche der eingegebenen Exemplare ausgesondert werden können. */
    public function preview(Request $request, CopyWithdrawalCheck $check, BusinessClock $clock): Response|RedirectResponse
    {
        $data = $request->validate(['numbers' => ['required', 'string', 'max:6000']], ['numbers.required' => 'Bitte mindestens eine Inventarnummer eingeben oder scannen.']);

        $numbers = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', $data['numbers']) ?: []))));

        if (count($numbers) > self::MAX_NUMBERS) {
            return back()->withInput()->withErrors(['numbers' => 'Bitte höchstens '.self::MAX_NUMBERS.' Nummern auf einmal.']);
        }

        $copies = Copy::query()->with('edition.title')->whereIn('barcode', $numbers)->get()->keyBy('barcode');

        $ready = [];
        $blocked = [];
        $already = [];
        $unknown = [];

        foreach ($numbers as $number) {
            $copy = $copies->get($number);

            if (! $copy instanceof Copy) {
                $unknown[] = $number;

                continue;
            }

            if ($copy->status === CopyStatus::Withdrawn) {
                $already[] = $copy;

                continue;
            }

            $problems = $check->problems($copy);

            if ($problems !== []) {
                $blocked[] = ['copy' => $copy, 'problems' => $problems];

                continue;
            }

            $ready[] = $copy;
        }

        if ($ready === []) {
            return redirect()->route('pos.withdrawal')->withInput()->with('withdrawal_report', ['unknown' => $unknown, 'blocked' => array_map(static fn (array $row): string => $row['copy']->barcode.' '.implode(' und ', $row['problems']), $blocked), 'already' => array_map(static fn (Copy $copy): string => $copy->barcode, $already)])
                ->withErrors(['numbers' => 'Keines der Exemplare kann jetzt ausgesondert werden.']);
        }

        return response()
            ->view('pages.surfaces.pos.withdrawal.confirm', [
                'ready' => $ready,
                'blocked' => $blocked,
                'already' => $already,
                'unknown' => $unknown,
                'reasons' => WithdrawalReason::cases(),
                'fates' => WithdrawalFate::cases(),
                'today' => $clock->now()->toDateString(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Schritt 3: Aussondern. Die Prüfung läuft noch einmal, falls inzwischen etwas ausgeliehen wurde. */
    public function store(Request $request, WithdrawCopiesAction $withdraw, CopyWithdrawalCheck $check, BusinessClock $clock): RedirectResponse
    {
        $data = $request->validate([
            'copies' => ['required', 'array', 'min:1', 'max:'.self::MAX_NUMBERS],
            'copies.*' => ['string', 'max:40'],
            'reason' => ['required', Rule::enum(WithdrawalReason::class)],
            'fate' => ['required', Rule::enum(WithdrawalFate::class)],
            'date' => ['required', 'date', 'before_or_equal:'.$clock->now()->toDateString()],
        ], [
            'copies.required' => 'Es sind keine Exemplare ausgewählt.',
            'reason.required' => 'Bitte einen Grund wählen.',
            'fate.required' => 'Bitte wählen, was mit den Büchern geschieht.',
            'date.before_or_equal' => 'Das Datum darf nicht in der Zukunft liegen.',
        ]);

        $ids = [];

        foreach (Copy::query()->whereIn('id', $data['copies'])->get() as $copy) {
            if ($check->problems($copy) === []) {
                $ids[] = (string) $copy->getKey();
            }
        }

        $count = $withdraw->execute($ids, WithdrawalReason::from($data['reason']), WithdrawalFate::from($data['fate']), CarbonImmutable::parse($data['date'])->toDateString());

        return redirect()->route('pos.withdrawal.list')->with('withdrawal_notice', $count.' '.($count === 1 ? 'Exemplar wurde' : 'Exemplare wurden').' ausgesondert.'.($count < count($data['copies']) ? ' Der Rest war inzwischen ausgeliehen oder zurückgelegt und blieb im Bestand.' : ''));
    }

    public function list(Request $request, BusinessClock $clock): Response
    {
        [$from, $to, $reason] = $this->filters($request, $clock);

        $copies = $this->query($from, $to, $reason)->limit(500)->get();

        return response()
            ->view('pages.surfaces.pos.withdrawal.list', [
                'copies' => $copies,
                'from' => $from,
                'to' => $to,
                'reason' => $reason,
                'reasons' => WithdrawalReason::cases(),
                'byReason' => $this->query($from, $to, $reason)->get()->groupBy(static fn (Copy $copy): string => WithdrawalReason::describe($copy->depreciation_reason))->map->count()->sortDesc()->all(),
                'total' => $this->query($from, $to, $reason)->count(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request, BusinessClock $clock): StreamedResponse
    {
        [$from, $to, $reason] = $this->filters($request, $clock);
        $copies = $this->query($from, $to, $reason)->get();

        return response()->streamDownload(static function () use ($copies): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            CsvExport::put($out, ['Inventarnummer', 'Titel', 'ISBN', 'Ausgesondert am', 'Grund', 'Verbleib'], ';');

            foreach ($copies as $copy) {
                CsvExport::put($out, [$copy->barcode, $copy->edition->title->preferred_title, $copy->edition->isbn, $copy->depreciated_at?->format('d.m.Y'), WithdrawalReason::describe($copy->depreciation_reason), WithdrawalFate::describe($copy->further_use)], ';');
            }

            fclose($out);
        }, 'aussonderungen.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function restore(string $copyId, RestoreWithdrawnCopyAction $restore): RedirectResponse
    {
        $copy = Copy::query()->with('edition.title')->findOrFail($copyId);

        $done = $restore->execute($copy);

        return redirect()->route('pos.withdrawal.list')->with('withdrawal_notice', $done
            ? '„'.$copy->edition->title->preferred_title.'“ ('.$copy->barcode.') ist wieder im Bestand.'
            : 'Dieses Exemplar war nicht ausgesondert.');
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function filters(Request $request, BusinessClock $clock): array
    {
        $today = $clock->now();
        $from = (string) $request->query('von', $today->subYear()->toDateString());
        $to = (string) $request->query('bis', $today->toDateString());
        $reason = (string) $request->query('grund', '');

        return [
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1 ? $from : $today->subYear()->toDateString(),
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1 ? $to : $today->toDateString(),
            $reason,
        ];
    }

    /** @return Builder<Copy> */
    private function query(string $from, string $to, string $reason)
    {
        return Copy::query()
            ->with('edition.title')
            ->where('status', CopyStatus::Withdrawn->value)
            ->whereBetween('depreciated_at', [$from, $to])
            ->when($reason !== '', static fn ($query) => $query->where('depreciation_reason', $reason))
            ->orderByDesc('depreciated_at')
            ->orderBy('barcode');
    }
}
