<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Bibliotheksausweise im Scheckkartenformat (85,6 × 54 mm), 2 × 5 je A4-Bogen. */
final class PatronCardController
{
    public const PER_SHEET = 10;

    public function index(Request $request): Response
    {
        $classId = (string) $request->query('klasse', '');
        $term = trim((string) $request->query('q', ''));

        $patrons = Patron::query()
            ->with('schoolClass')
            ->where('status', PatronStatus::Active->value)
            ->when($classId !== '', static fn ($query) => $query->where('school_class_id', $classId))
            ->when($term !== '', static function ($query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(static function ($inner) use ($like): void {
                    $inner->where('last_name', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('library_number', 'like', $like);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(300)
            ->get();

        return response()
            ->view('pages.surfaces.pos.labels.cards-index', [
                'patrons' => $patrons,
                'classes' => SchoolClass::query()->where('is_active', true)->whereRelation('schoolYear', 'is_active', true)->orderBy('grade_level')->orderBy('name')->get(),
                'classId' => $classId,
                'term' => $term,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function print(Request $request): Response
    {
        $data = $request->validate([
            'patrons' => ['required', 'array', 'min:1', 'max:300'],
            'patrons.*' => ['string', 'max:40'],
        ], ['patrons.required' => 'Bitte mindestens ein Ausleihkonto auswählen.']);

        $patrons = Patron::query()
            ->with('schoolClass')
            ->where('status', PatronStatus::Active->value)
            ->whereIn('id', $data['patrons'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()
            ->view('pages.surfaces.pos.labels.cards-print', ['patrons' => $patrons, 'perSheet' => self::PER_SHEET])
            ->header('Cache-Control', 'private, no-store');
    }
}
