<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Surfaces\Pos\Support\HelpLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Hilfe für den Bibliotheksbetrieb: viele kurze Artikel mit Suche und Filtern (Markdown unter `resources/help/artikel`). */
final class HelpController
{
    public function index(Request $request, HelpLibrary $library): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'bereich' => ['nullable', Rule::in(array_keys(HelpLibrary::AREAS))],
            'rolle' => ['nullable', Rule::in(array_keys(HelpLibrary::ROLES))],
        ]);

        $query = trim((string) ($filters['q'] ?? ''));
        $area = (string) ($filters['bereich'] ?? '');
        $role = (string) ($filters['rolle'] ?? '');
        $hits = $library->search($query, $area, $role);
        $filtered = $query !== '' || $area !== '' || $role !== '';

        return response()
            ->view('pages.surfaces.pos.help', [
                'areas' => HelpLibrary::AREAS,
                'roles' => HelpLibrary::ROLES,
                'query' => $query,
                'area' => $area,
                'role' => $role,
                'filtered' => $filtered,
                'hits' => $hits,
                'grouped' => $filtered ? [] : $this->group($hits),
                'total' => count($library->all()),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(string $topic, Request $request, HelpLibrary $library): Response|RedirectResponse
    {
        // Die drei früheren Sammelseiten gibt es nicht mehr: Sie führen zur Liste des Bereichs.
        if (array_key_exists($topic, HelpLibrary::LEGACY)) {
            return redirect()->route('pos.help', ['bereich' => HelpLibrary::LEGACY[$topic]]);
        }

        $article = $library->find($topic);
        abort_if($article === null, 404);

        return response()->view('pages.surfaces.pos.help-article', [
            'article' => $article,
            'areas' => HelpLibrary::AREAS,
            'roles' => HelpLibrary::ROLES,
            // Eigene, versionierte Texte; trotzdem wird eingebettetes HTML nicht ausgeführt.
            'html' => Str::markdown($article['body'], ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            'related' => $library->related($article['slug']),
            'query' => trim((string) $request->query('q', '')),
        ]);
    }

    /**
     * @param  list<array{article: array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}, snippet: string|null}>  $hits
     * @return array<string, list<array{slug: string, title: string, summary: string, area: string, roles: list<string>, keywords: list<string>, body: string}>>
     */
    private function group(array $hits): array
    {
        $grouped = [];

        foreach ($hits as $hit) {
            $grouped[$hit['article']['area']][] = $hit['article'];
        }

        return $grouped;
    }
}
