<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Actions\DeleteCatalogShelfAction;
use App\Modules\Catalog\Actions\SaveCatalogShelfAction;
use App\Modules\Catalog\Enums\ShelfSectionKind;
use App\Modules\Catalog\Exceptions\CatalogShelfInUse;
use App\Modules\Catalog\Exceptions\CatalogShelfStructureConflict;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Regale und Regalbretter: Bereichsgruppe › Bereich › Regal › Regalbrett, samt Themenbereichen für den Vorschlag beim Einsortieren. */
final class CatalogShelfController
{
    public function index(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));
        $withoutTopic = $request->boolean('ohne_thema');
        $match = $this->matchingShelves($term, $withoutTopic);

        $groups = CatalogShelfSection::query()
            ->where('kind', ShelfSectionKind::Group->value)
            ->orderBy('sort_order')->orderBy('code')
            ->with(['children.children.shelves.topics'])
            ->get();

        $unassigned = CatalogShelf::query()->whereNull('section_id')->with('topics')->orderBy('sort_order')->orderBy('code')->get();

        $counts = Copy::query()->whereNotNull('shelf_location')->selectRaw('shelf_location, count(*) as total')->groupBy('shelf_location')->pluck('total', 'shelf_location')->all();

        $racks = [];

        foreach ($groups as $group) {
            foreach ($group->children as $area) {
                foreach ($area->children as $rack) {
                    $racks[(string) $rack->getKey()] = $group->code.' › '.$area->code.' › '.$rack->code.($rack->name ? ' · '.$rack->name : '');
                }
            }
        }

        return response()
            ->view('pages.surfaces.administration.shelves.index', [
                'groups' => $groups,
                'unassigned' => $unassigned,
                'counts' => $counts,
                'rackOptions' => $racks,
                'shelfTotal' => CatalogShelf::query()->count(),
                'term' => $term,
                'withoutTopic' => $withoutTopic,
                'match' => $match,
                'topicGroups' => $this->topicGroups(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, SaveCatalogShelfAction $save): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $shelf = $save->execute(null, $data['code'] ?? null, $data['label'] ?? null, (int) ($data['sort_order'] ?? 0), true, array_values($data['topics'] ?? []), $data['rack_id'] ?? null, $data['board'] ?? null, isset($data['capacity']) ? (int) $data['capacity'] : null);
        } catch (CatalogShelfStructureConflict $exception) {
            return redirect()->route('administration.shelves.index')->withErrors(['shelf' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('administration.shelves.index')->with('shelf_success', 'Das Regalbrett „'.$shelf->code.'“ ist angelegt.');
    }

    public function update(Request $request, string $shelfId, SaveCatalogShelfAction $save): RedirectResponse
    {
        $shelf = CatalogShelf::query()->findOrFail($shelfId);
        $data = $this->validated($request);

        try {
            $shelf = $save->execute($shelf, $data['code'] ?? null, $data['label'] ?? null, (int) ($data['sort_order'] ?? 0), $request->boolean('is_active'), array_values($data['topics'] ?? []), $data['rack_id'] ?? null, $data['board'] ?? null, isset($data['capacity']) ? (int) $data['capacity'] : null);
        } catch (CatalogShelfStructureConflict $exception) {
            return redirect()->route('administration.shelves.index')->withErrors(['shelf' => $exception->getMessage()]);
        }

        return redirect()->route('administration.shelves.index')->with('shelf_success', 'Das Regalbrett „'.$shelf->code.'“ ist gespeichert.');
    }

    public function destroy(string $shelfId, DeleteCatalogShelfAction $delete): RedirectResponse
    {
        $shelf = CatalogShelf::query()->findOrFail($shelfId);

        try {
            $delete->execute($shelf);
        } catch (CatalogShelfInUse $exception) {
            return redirect()->route('administration.shelves.index')->withErrors(['shelf' => $exception->getMessage()]);
        }

        return redirect()->route('administration.shelves.index')->with('shelf_success', 'Das Regalbrett „'.$shelf->code.'“ ist gelöscht.');
    }

    /**
     * Die Regalbretter, die zu Suchbegriff und Filter passen (null: keine Einschränkung). Gesucht wird in Standort, Beschriftung, Themen
     * sowie den Namen von Regal, Bereich und Bereichsgruppe; mehrere Wörter müssen alle vorkommen.
     *
     * @return list<string>|null
     */
    private function matchingShelves(string $term, bool $withoutTopic): ?array
    {
        if ($term === '' && ! $withoutTopic) {
            return null;
        }

        $words = array_values(array_filter(preg_split('/\s+/', mb_strtolower($term)) ?: [], static fn (string $word): bool => $word !== ''));

        return CatalogShelf::query()->with(['topics', 'rack.parent.parent'])->get()
            ->filter(static function (CatalogShelf $shelf) use ($words, $withoutTopic): bool {
                if ($withoutTopic && $shelf->topics->isNotEmpty()) {
                    return false;
                }

                $haystack = mb_strtolower(implode(' ', array_filter([
                    $shelf->code,
                    $shelf->label,
                    $shelf->topics->pluck('name')->implode(' '),
                    $shelf->rack?->display(),
                    $shelf->rack?->parent?->display(),
                    $shelf->rack?->parent?->parent?->display(),
                ])));

                foreach ($words as $word) {
                    if (! str_contains($haystack, $word)) {
                        return false;
                    }
                }

                return true;
            })
            ->map(static fn (CatalogShelf $shelf): string => (string) $shelf->getKey())
            ->values()
            ->all();
    }

    /**
     * Themenbereiche als Gruppen: Hauptbereich mit seinen Unterbereichen, für die Auswahl.
     *
     * @return list<array{root: CatalogTopic, children: list<CatalogTopic>}>
     */
    private function topicGroups(): array
    {
        $topics = CatalogTopic::query()->orderBy('name')->get();
        $groups = [];

        foreach ($topics->whereNull('parent_id') as $root) {
            $groups[] = ['root' => $root, 'children' => $topics->where('parent_id', $root->getKey())->values()->all()];
        }

        return $groups;
    }

    /**
     * Mit Regal und Bezeichnung des Bretts setzt sich der Standort selbst zusammen, sonst braucht das Regalbrett einen freien Code.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $hasRack = $request->filled('rack_id');

        return $request->validate([
            'rack_id' => ['nullable', 'string', Rule::exists('catalog_shelf_sections', 'id')->where('kind', ShelfSectionKind::Rack->value)],
            'board' => [$hasRack ? 'required' : 'nullable', 'string', 'max:20'],
            'code' => [$hasRack ? 'nullable' : 'required', 'string', 'max:40'],
            'label' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'capacity' => ['nullable', 'integer', 'between:1,9999'],
            'is_active' => ['nullable', 'boolean'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', Rule::exists('catalog_topics', 'id')],
        ], $this->messages());
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'board.required' => 'Bitte die Bezeichnung des Regalbretts im Regal angeben, zum Beispiel „a“.',
            'code.required' => 'Bitte einen Standort-Code angeben (oder ein Regal wählen).',
            'code.max' => 'Der Standort-Code darf höchstens 40 Zeichen lang sein.',
        ];
    }
}
