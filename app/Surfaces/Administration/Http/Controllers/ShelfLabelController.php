<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Support\Code128Svg;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\ShelfSectionKind;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Etiketten für Regalbretter: Das Thema steht groß, der Standort klein unten rechts, dazu ein Strichcode oder QR-Code zum Scannen beim
 * Einsortieren (einstellbar). Format 105 × 26 mm, 2 × 11 = 22 Etiketten je A4-Bogen.
 */
final class ShelfLabelController
{
    public const PER_SHEET = 22;

    /** Kleinste Strichbreite (mm), bei der ein Strichcode noch zuverlässig lesbar bleibt, und die Breite, die dafür auf dem Etikett bleibt. */
    private const MODULE_MM = 0.25;

    private const BARCODE_MAX_MM = 46;

    public function index(): Response
    {
        $groups = CatalogShelfSection::query()
            ->where('kind', ShelfSectionKind::Group->value)
            ->orderBy('sort_order')->orderBy('code')
            ->with('children.children.shelves')
            ->get();

        return response()
            ->view('pages.surfaces.administration.shelves.labels', [
                'groups' => $groups,
                'loose' => CatalogShelf::query()->whereNull('section_id')->orderBy('sort_order')->orderBy('code')->get(),
                'total' => CatalogShelf::query()->where('is_active', true)->count(),
                'perSheet' => self::PER_SHEET,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function print(Request $request, AuditRecorder $audit): Response
    {
        $data = $request->validate([
            'umfang' => ['nullable', 'string', 'max:40', 'regex:/^(alle|lose|[gar]:[0-9a-z]{26})$/i'],
            'bretter' => ['nullable', 'array'],
            'bretter.*' => ['string', 'regex:/^[0-9a-z]{26}$/i'],
            'anzahl' => ['nullable', 'integer', 'between:1,4'],
            'startplatz' => ['nullable', 'integer', 'between:1,'.self::PER_SHEET],
            'inaktive' => ['nullable', 'boolean'],
            'themen' => ['nullable', 'boolean'],
            'code' => ['nullable', 'in:strich,qr,keiner'],
        ]);

        $shelves = $this->shelves($data, $request->boolean('inaktive'));

        if ($shelves->isEmpty()) {
            return response()->view('pages.surfaces.administration.shelves.labels', [
                'groups' => CatalogShelfSection::query()->where('kind', ShelfSectionKind::Group->value)->orderBy('sort_order')->orderBy('code')->with('children.children.shelves')->get(),
                'loose' => CatalogShelf::query()->whereNull('section_id')->orderBy('sort_order')->orderBy('code')->get(),
                'total' => CatalogShelf::query()->where('is_active', true)->count(),
                'perSheet' => self::PER_SHEET,
                'empty' => true,
            ], 422);
        }

        $copies = (int) ($data['anzahl'] ?? 1);
        $labels = [];

        foreach ($shelves as $shelf) {
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = $this->label($shelf);
            }
        }

        $audit->record('catalog.labels.shelves_printed', count($labels).' Regalbrett-Etiketten gedruckt ('.$shelves->count().' Regalbretter).', null, ['labels' => count($labels), 'shelves' => $shelves->count()], (int) $request->user()?->getAuthIdentifier());

        return response()
            ->view('pages.surfaces.administration.shelves.labels-print', [
                'labels' => $labels,
                'skip' => max(0, ((int) ($data['startplatz'] ?? 1)) - 1),
                'perSheet' => self::PER_SHEET,
                'withTopics' => $request->boolean('themen'),
                'codeType' => (string) ($data['code'] ?? 'strich'),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, CatalogShelf>
     */
    private function shelves(array $data, bool $inactive): Collection
    {
        $query = CatalogShelf::query()
            ->with(['topics', 'rack.parent.parent'])
            ->when(! $inactive, static fn ($query) => $query->where('is_active', true));

        $ids = array_values(array_filter((array) ($data['bretter'] ?? [])));

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        } else {
            $scope = (string) ($data['umfang'] ?? 'alle');

            if ($scope === 'lose') {
                $query->whereNull('section_id');
            } elseif (preg_match('/^([gar]):(.+)$/i', $scope, $m) === 1) {
                $racks = $this->rackIds(strtolower($m[1]), $m[2]);
                $query->whereIn('section_id', $racks);
            }
        }

        return $query->get()
            ->sortBy(fn (CatalogShelf $shelf): string => sprintf(
                '%s|%s|%s|%05d|%s',
                $shelf->rack?->parent?->parent?->sort_order !== null ? sprintf('%05d', $shelf->rack->parent->parent->sort_order) : '99999',
                $shelf->rack?->parent !== null ? sprintf('%05d', $shelf->rack->parent->sort_order) : '99999',
                $shelf->rack !== null ? sprintf('%05d', $shelf->rack->sort_order) : '99999',
                $shelf->sort_order,
                $shelf->code,
            ))
            ->values();
    }

    /** @return list<string> */
    private function rackIds(string $level, string $id): array
    {
        if ($level === 'r') {
            return [$id];
        }

        $areaIds = $level === 'a' ? [$id] : CatalogShelfSection::query()->where('parent_id', $id)->pluck('id')->map(static fn (mixed $v): string => (string) $v)->all();

        return CatalogShelfSection::query()->whereIn('parent_id', $areaIds)->where('kind', ShelfSectionKind::Rack->value)->pluck('id')->map(static fn (mixed $v): string => (string) $v)->all();
    }

    /** @return array{code: string, headline: string, label: string, where: string, topics: string, barcode: bool} */
    private function label(CatalogShelf $shelf): array
    {
        $rack = $shelf->rack;
        $area = $rack?->parent;
        $group = $area?->parent;

        $where = array_values(array_filter([
            $rack?->display(),
            $area?->display(),
            $group?->display(),
        ]));

        $topics = $shelf->topics->pluck('name')->implode(' / ');
        $label = trim((string) $shelf->label);

        return [
            'code' => $shelf->code,
            // Das Thema steht groß: die Beschriftung des Bretts, sonst die Themenbereiche, sonst der Standort.
            'headline' => mb_substr($label !== '' ? $label : ($topics !== '' ? $topics : $shelf->code), 0, 90),
            'label' => mb_substr($label, 0, 80),
            'where' => implode('  ›  ', $where),
            'topics' => mb_substr($shelf->topics->pluck('name')->implode(', '), 0, 100),
            'barcode' => preg_match('/^[\x20-\x7E]+$/', $shelf->code) === 1
                && Code128Svg::modules($shelf->code) * self::MODULE_MM <= self::BARCODE_MAX_MM,
        ];
    }
}
