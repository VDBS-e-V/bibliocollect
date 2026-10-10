<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Actions\DeleteCatalogTopicAction;
use App\Modules\Catalog\Actions\SaveCatalogTopicAction;
use App\Modules\Catalog\Actions\ToggleFeaturedAction;
use App\Modules\Catalog\Exceptions\CatalogTaxonomyInUse;
use App\Modules\Catalog\Models\CatalogTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Themenbereiche mit Unterbereichen, wie sie beim Erfassen, im öffentlichen Katalog und bei den Regalbrettern erscheinen. */
final class CatalogTopicController
{
    public function index(): Response
    {
        $topics = CatalogTopic::query()->with('shelves')->withCount(['children', 'shelves'])->orderBy('name')->get();

        return response()
            ->view('pages.surfaces.administration.topics.index', [
                'roots' => $topics->whereNull('parent_id')->values(),
                'topics' => $topics,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Thema auf der Startseite empfehlen oder die Empfehlung zurücknehmen. */
    public function feature(string $topicId, ToggleFeaturedAction $toggle): RedirectResponse
    {
        $topic = CatalogTopic::query()->findOrFail($topicId);
        $featured = $toggle->execute($topic);

        return redirect()->route('administration.topics.index')->with('taxonomy_success', $featured ? 'Das Thema „'.$topic->name.'“ wird auf der Startseite empfohlen.' : 'Die Empfehlung für „'.$topic->name.'“ ist zurückgenommen.');
    }

    public function store(Request $request, SaveCatalogTopicAction $save): RedirectResponse
    {
        $data = $this->validated($request);
        $save->execute(null, $data['name'], $data['public_key'] ?? null, $data['parent_id'] ?? null, $data['description'] ?? null);

        return redirect()->route('administration.topics.index')->with('taxonomy_success', 'Der Themenbereich „'.trim($data['name']).'“ ist angelegt.');
    }

    public function update(Request $request, string $topicId, SaveCatalogTopicAction $save): RedirectResponse
    {
        $topic = CatalogTopic::query()->findOrFail($topicId);
        $data = $this->validated($request);

        try {
            $save->execute($topic, $data['name'], $data['public_key'] ?? null, $data['parent_id'] ?? null, $data['description'] ?? null);
        } catch (CatalogTaxonomyInUse $exception) {
            return redirect()->route('administration.topics.index')->withErrors(['topic' => $exception->getMessage()]);
        }

        return redirect()->route('administration.topics.index')->with('taxonomy_success', 'Der Themenbereich „'.trim($data['name']).'“ ist gespeichert.');
    }

    public function destroy(Request $request, string $topicId, DeleteCatalogTopicAction $delete): RedirectResponse
    {
        $topic = CatalogTopic::query()->findOrFail($topicId);

        try {
            $children = (string) $request->input('children', DeleteCatalogTopicAction::CHILDREN_BLOCK);
            abort_unless(in_array($children, [DeleteCatalogTopicAction::CHILDREN_BLOCK, DeleteCatalogTopicAction::CHILDREN_MOVE, DeleteCatalogTopicAction::CHILDREN_DELETE], true), 422);

            $result = $delete->execute($topic, $children, $request->boolean('detach_shelves'));
        } catch (CatalogTaxonomyInUse $exception) {
            return redirect()->route('administration.topics.index')->withErrors(['topic' => $exception->getMessage()]);
        }

        return redirect()->route('administration.topics.index')->with('taxonomy_success', 'Der Themenbereich „'.$topic->name.'“ ist gelöscht.'
            .($result['deleted'] > 1 ? ' Mit ihm '.($result['deleted'] - 1).' Unterbereich(e).' : '')
            .($result['moved'] > 0 ? ' '.$result['moved'].' Unterbereich(e) sind eine Ebene nach oben gerückt.' : ''));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'public_key' => ['nullable', 'string', 'max:40'],
            'parent_id' => ['nullable', 'string', Rule::exists('catalog_topics', 'id')],
            'description' => ['nullable', 'string', 'max:300'],
        ], ['name.required' => 'Bitte einen Namen für den Themenbereich angeben.']);
    }
}
