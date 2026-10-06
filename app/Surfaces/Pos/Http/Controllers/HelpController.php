<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Anleitungen für den Bibliotheksbetrieb, geschrieben als Markdown unter `resources/help`. */
final class HelpController
{
    /** @var array<string, string> */
    private const TOPICS = [
        'ausleihe' => 'Ausleihe am Tresen',
        'katalog' => 'Katalog und Ausleihkonten pflegen',
        'verwaltung' => 'Verwaltung',
    ];

    public function index(): Response
    {
        return response()->view('pages.surfaces.pos.help', ['topics' => self::TOPICS, 'current' => null, 'html' => null]);
    }

    public function show(string $topic): Response
    {
        abort_unless(array_key_exists($topic, self::TOPICS), 404);

        $markdown = (string) file_get_contents(resource_path('help/'.$topic.'.md'));

        return response()->view('pages.surfaces.pos.help', [
            'topics' => self::TOPICS,
            'current' => $topic,
            // Eigene, versionierte Texte; trotzdem wird eingebettetes HTML nicht ausgeführt.
            'html' => $this->demoteHeadings(Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false])),
        ]);
    }

    /** Die Seitenüberschrift ist schon die Hauptüberschrift; im Text rücken alle Überschriften eine Ebene tiefer. */
    private function demoteHeadings(string $html): string
    {
        return strtr($html, ['<h3>' => '<h4>', '</h3>' => '</h4>', '<h2>' => '<h3>', '</h2>' => '</h3>', '<h1>' => '<h2>', '</h1>' => '</h2>']);
    }
}
