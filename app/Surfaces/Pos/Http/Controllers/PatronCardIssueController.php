<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\AssignPatronCardAction;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Exceptions\PatronCardConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\School\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Ausweise klassenweise ausgeben: Je Person ein Scanfeld. Nach dem Scannen springt der Cursor zur nächsten Person
 * ohne Ausweis. Dieselbe Seite ist die Auswertung „wer hat noch keinen Ausweis“ (Filter, Druck).
 */
final class PatronCardIssueController
{
    private const LIMIT = 400;

    public function index(Request $request): Response
    {
        $classId = (string) $request->query('klasse', '');
        $onlyMissing = $request->boolean('nur_ohne');

        $patrons = collect();

        if ($classId !== '') {
            $patrons = Patron::query()
                ->with('schoolClass')
                ->where('status', PatronStatus::Active->value)
                ->when($classId === 'ohne', static fn ($query) => $query->whereNull('school_class_id'))
                ->when($classId !== 'alle' && $classId !== 'ohne', static fn ($query) => $query->where('school_class_id', $classId))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(self::LIMIT)
                ->get();
        }

        $cards = PatronCard::query()
            ->whereIn('patron_id', $patrons->pluck('id')->all())
            ->where('status', CardStatus::Assigned->value)
            ->pluck('number', 'patron_id')
            ->all();

        $missing = $patrons->filter(static fn (Patron $patron): bool => ! isset($cards[(string) $patron->getKey()]))->count();

        if ($onlyMissing) {
            $patrons = $patrons->filter(static fn (Patron $patron): bool => ! isset($cards[(string) $patron->getKey()]))->values();
        }

        if ($classId === 'alle') {
            // Nach Klasse gruppiert lesen sich Ausgabelisten besser.
            $patrons = $patrons->sortBy(static fn (Patron $patron): string => ($patron->getAttribute('school_class_id') === null ? '~' : $patron->schoolClass->name).'|'.$patron->last_name.'|'.$patron->first_name)->values();
        }

        return response()
            ->view('pages.surfaces.pos.labels.cards-issue', [
                'classes' => SchoolClass::query()->where('is_active', true)->whereRelation('schoolYear', 'is_active', true)->orderBy('grade_level')->orderBy('name')->get(),
                'classId' => $classId,
                'onlyMissing' => $onlyMissing,
                'patrons' => $patrons,
                'cards' => $cards,
                'missing' => $missing,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, AssignPatronCardAction $assign): RedirectResponse
    {
        $data = $request->validate([
            'patron_id' => ['required', 'string', 'max:40'],
            'code' => ['required', 'string', 'max:40'],
            'klasse' => ['nullable', 'string', 'max:40'],
            'nur_ohne' => ['nullable', 'boolean'],
        ], ['code.required' => 'Bitte den Ausweis scannen.']);

        $back = ['klasse' => $data['klasse'] ?? '', 'nur_ohne' => $request->boolean('nur_ohne') ? 1 : null];
        $patron = Patron::query()->find($data['patron_id']);

        if (! $patron instanceof Patron) {
            return redirect()->route('pos.labels.cards.issue', $back)->with('issue_error', 'Diese Person gibt es nicht.');
        }

        try {
            $replaced = $assign->execute(trim($data['code']), $patron);
        } catch (PatronCardConflict $exception) {
            return redirect()->route('pos.labels.cards.issue', $back)->with('issue_error', $patron->displayName().': '.$exception->getMessage());
        }

        return redirect()
            ->route('pos.labels.cards.issue', $back)
            ->with('issue_notice', 'Ausweis '.trim($data['code']).' ist '.$patron->displayName().' zugeordnet.'.($replaced > 0 ? ' Der frühere Ausweis wurde gesperrt.' : ''));
    }
}
