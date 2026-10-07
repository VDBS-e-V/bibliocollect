<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Übersicht aller Vorgänge des Bibliotheksbetriebs, gefiltert nach den Rechten der angemeldeten Person. */
final class ProcessesController
{
    public function __invoke(): Response
    {
        $groups = [];

        /** @var list<array{title: string, items: list<array{label: string, text: string, route: string, permission: ?string}>}> $configured */
        $configured = config('processes.groups', []);

        foreach ($configured as $group) {
            $items = array_values(array_filter(
                $group['items'],
                static fn (array $item): bool => $item['permission'] === null || Gate::allows($item['permission']),
            ));

            if ($items !== []) {
                $groups[] = ['title' => $group['title'], 'items' => $items];
            }
        }

        return response()
            ->view('pages.surfaces.pos.processes', ['groups' => $groups])
            ->header('Cache-Control', 'private, no-store');
    }
}
