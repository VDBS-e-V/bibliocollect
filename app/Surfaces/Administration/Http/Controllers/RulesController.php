<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Settings\SettingsRegistry;
use App\Foundation\Settings\SettingsRepository;
use App\Modules\Audit\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/** Seite „Regeln“: Leihfristen, Höchstzahlen, Vormerken und Erinnerungen ohne Änderung an Dateien einstellen. */
final class RulesController
{
    public function index(SettingsRegistry $registry, SettingsRepository $settings): Response
    {
        $values = [];
        $defaults = [];

        foreach ($registry->all() as $key => $definition) {
            $values[$definition['field']] = $settings->current($key);
            $defaults[$definition['field']] = $settings->default($key);
        }

        return response()
            ->view('pages.surfaces.administration.rules.index', [
                'groups' => $registry->groups(),
                'values' => $values,
                'defaults' => $defaults,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, SettingsRegistry $registry, SettingsRepository $settings, AuditRecorder $audit): RedirectResponse
    {
        $rules = [];
        $names = [];

        foreach ($registry->all() as $definition) {
            $names[$definition['field']] = $definition['label'];
            $rules[$definition['field']] = $definition['type'] === SettingsRegistry::BOOL
                ? ['nullable', 'boolean']
                : [$definition['nullable'] ? 'nullable' : 'required', 'integer', 'min:'.$definition['min'], 'max:'.$definition['max']];
        }

        $data = Validator::make($request->all(), $rules, [], $names)->validate();

        $new = [];

        foreach ($registry->all() as $key => $definition) {
            $raw = $data[$definition['field']] ?? null;
            $new[$key] = $definition['type'] === SettingsRegistry::BOOL
                ? (bool) $raw
                : ($raw === null || $raw === '' ? null : (int) $raw);
        }

        $changed = $settings->save($new, $request->user()?->getAuthIdentifier() !== null ? (int) $request->user()->getAuthIdentifier() : null);

        if ($changed === []) {
            return redirect()->route('administration.rules.index')->with('rules_success', 'Es gab nichts zu ändern.');
        }

        $labels = array_map(static fn (string $key): string => $registry->all()[$key]['label'], $changed);
        $audit->record('settings.updated', 'Regeln geändert: '.implode(', ', $labels).'.', null, ['keys' => implode(',', $changed)]);

        return redirect()->route('administration.rules.index')->with('rules_success', count($changed).' Regel(n) gespeichert. Die Änderung gilt sofort.');
    }

    public function reset(SettingsRepository $settings, AuditRecorder $audit): RedirectResponse
    {
        $settings->reset();
        $audit->record('settings.reset', 'Alle Regeln auf die Standardwerte zurückgesetzt.');

        return redirect()->route('administration.rules.index')->with('rules_success', 'Alle Regeln gelten wieder mit den Standardwerten.');
    }
}
