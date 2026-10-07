<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Actions\DeleteCatalogSignatureAction;
use App\Modules\Catalog\Actions\SaveCatalogSignatureAction;
use App\Modules\Catalog\Exceptions\CatalogTaxonomyInUse;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Signaturen: die Regalgruppen, die Themenbereiche zusammenfassen. Bücher bekommen eine Signatur, daraus folgt das Regalbrett. */
final class CatalogSignatureController
{
    public function index(): Response
    {
        $signatures = CatalogSignature::query()->with('topics')->withCount('copies')->get()->sort(static fn (CatalogSignature $a, CatalogSignature $b): int => strnatcasecmp($a->signature, $b->signature))->values();

        return response()
            ->view('pages.surfaces.administration.signatures.index', [
                'signatures' => $signatures,
                'topicGroups' => $this->topicGroups(),
                'shelves' => CatalogShelf::query()->whereNotNull('signature_id')->pluck('code', 'signature_id')->all(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, SaveCatalogSignatureAction $save): RedirectResponse
    {
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:60', Rule::unique('catalog_signatures', 'signature')],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', Rule::exists('catalog_topics', 'id')],
        ], $this->messages());

        $save->execute(null, $data['signature'], array_values($data['topics'] ?? []));

        return redirect()->route('administration.signatures.index')->with('taxonomy_success', 'Die Signatur „'.trim($data['signature']).'“ ist angelegt.');
    }

    public function update(Request $request, string $signatureId, SaveCatalogSignatureAction $save): RedirectResponse
    {
        $signature = CatalogSignature::query()->findOrFail($signatureId);

        $data = $request->validate([
            'signature' => ['required', 'string', 'max:60', Rule::unique('catalog_signatures', 'signature')->ignore($signature->getKey())],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', Rule::exists('catalog_topics', 'id')],
        ], $this->messages());

        $save->execute($signature, $data['signature'], array_values($data['topics'] ?? []));

        return redirect()->route('administration.signatures.index')->with('taxonomy_success', 'Die Signatur „'.trim($data['signature']).'“ ist gespeichert.');
    }

    public function destroy(string $signatureId, DeleteCatalogSignatureAction $delete): RedirectResponse
    {
        $signature = CatalogSignature::query()->findOrFail($signatureId);

        try {
            $delete->execute($signature);
        } catch (CatalogTaxonomyInUse $exception) {
            return redirect()->route('administration.signatures.index')->withErrors(['signature' => $exception->getMessage()]);
        }

        return redirect()->route('administration.signatures.index')->with('taxonomy_success', 'Die Signatur „'.$signature->signature.'“ ist gelöscht.');
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

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'signature.required' => 'Bitte die Signatur angeben, zum Beispiel „I. A 1 a“.',
            'signature.unique' => 'Diese Signatur gibt es schon.',
            'signature.max' => 'Die Signatur darf höchstens 60 Zeichen lang sein.',
        ];
    }
}
