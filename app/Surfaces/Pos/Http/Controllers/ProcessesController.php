<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Übersicht aller Vorgänge, getrennt in Betrieb und Verwaltung und gefiltert nach den Rechten der angemeldeten Person. */
final class ProcessesController
{
    public function __invoke(): Response
    {
        $areas = [];

        /** @var list<array{key: string, title: string, lead: string, groups: list<array{title: string, items: list<array{label: string, text: string, route: string, permission: ?string}>}>}> $configured */
        $configured = config('processes.areas', []);

        foreach ($configured as $area) {
            $groups = [];

            foreach ($area['groups'] as $group) {
                $items = array_values(array_filter(
                    $group['items'],
                    static fn (array $item): bool => $item['permission'] === null || Gate::allows($item['permission']),
                ));

                if ($items !== []) {
                    $groups[] = ['title' => $group['title'], 'items' => $items];
                }
            }

            if ($groups !== []) {
                $areas[] = ['key' => $area['key'], 'title' => $area['title'], 'lead' => $area['lead'], 'groups' => $groups];
            }
        }

        return response()
            ->view('pages.surfaces.pos.processes', ['areas' => $areas])
            ->header('Cache-Control', 'private, no-store');
    }
}
