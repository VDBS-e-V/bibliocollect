<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Foundation\Console\CronCommand;
use App\Foundation\Support\SystemHealth;
use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Content\Models\ContentPage;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

/** Startseite der Verwaltung: Systemzustand, die Startklar-Prüfung und die Verwaltungsaufgaben nach Rechten. */
final class AdminHomeController
{
    public function __invoke(SystemHealth $health): Response
    {
        return response()
            ->view('pages.surfaces.administration', [
                'preview' => false,
                'snapshot' => Gate::allows('system.view') ? $health->snapshot() : null,
                'checklist' => $this->checklist(),
                'areas' => $this->areas(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** @return list<array{label: string, ok: bool, detail: string, route: ?string}> */
    private function checklist(): array
    {
        $year = SchoolYear::query()->where('is_active', true)->first();
        $classes = $year !== null ? SchoolClass::query()->where('school_year_id', $year->getKey())->where('is_active', true)->count() : 0;
        $placeholders = ContentPage::query()->whereIn('slug', ['impressum', 'datenschutz', 'barrierefreiheit'])->where('is_placeholder', true)->count();
        $heartbeat = Cache::get(CronCommand::HEARTBEAT_KEY);
        $cronOk = is_string($heartbeat) && now()->diffInMinutes($heartbeat, true) < 120;
        $backupFiles = is_dir(storage_path('app/backups')) ? File::files(storage_path('app/backups')) : [];
        $backupOk = collect($backupFiles)->contains(static fn ($file): bool => $file->getMTime() > now()->subDays(2)->getTimestamp());
        $demo = User::query()->where('email', 'like', '%@demo.bibliocollect.test')->count();
        $alert = config('hosting.alert_email');

        return [
            $this->entry('Schuljahr und Klassen', $year !== null && $classes > 0, $year !== null ? "Aktives Schuljahr {$year->name}, {$classes} Klassen" : 'Kein Schuljahr aktiv', 'administration.school.index', 'school.manage'),
            $this->entry('Öffnungszeiten', LibraryOpeningHour::query()->where('is_open', true)->exists(), 'Wochentage mit Zeiten und Schließtage', 'administration.calendar.index', 'school.manage'),
            $this->entry('Impressum, Datenschutz, Barrierefreiheit', $placeholders === 0, $placeholders === 0 ? 'Alle drei Seiten sind ausgefüllt' : "{$placeholders} Seite(n) sind noch Platzhalter", 'administration.pages.index', 'content.manage'),
            $this->entry('Ausleihkonten', Patron::query()->exists(), 'Konten über „Klassendaten importieren“ oder einzeln anlegen'),
            $this->entry('Ausweise', PatronCard::query()->exists(), 'Ausweise erzeugen und drucken'),
            $this->entry('Bestand', Copy::query()->exists(), 'Exemplare im Katalog'),
            $this->entry('Mailversand', ! in_array((string) config('mail.default'), ['log', 'array'], true), 'Echter Mailserver statt Logdatei (Einladungen, Bestätigungen, Erinnerungen)'),
            $this->entry('Betriebsmeldungen', is_string($alert) && trim($alert) !== '', 'ALERT_EMAIL in der .env', 'administration.system.index', 'system.view'),
            $this->entry('Cronjob läuft', $cronOk, 'Zeitplan und Warteschlange (Sicherung, Erinnerungen, Cover)', 'administration.system.index', 'system.view'),
            $this->entry('Datensicherung', $backupOk, 'Eine Sicherung der letzten zwei Tage', 'administration.system.index', 'system.view'),
            $this->entry('Demo-Konten entfernt', $demo === 0, $demo === 0 ? 'Keine Demo-Konten vorhanden' : "{$demo} Demo-Konto/Konten mit bekanntem Passwort", 'administration.users.index', 'users.manage'),
        ];
    }

    /** @return array{label: string, ok: bool, detail: string, route: ?string} */
    private function entry(string $label, bool $ok, string $detail, ?string $route = null, ?string $permission = null): array
    {
        return [
            'label' => $label,
            'ok' => $ok,
            'detail' => $detail,
            'route' => $route !== null && ($permission === null || Gate::allows($permission)) ? route($route) : null,
        ];
    }

    /** @return list<array{title: string, items: list<array{label: string, text: string, route: string}>}> */
    private function areas(): array
    {
        $groups = [];

        /** @var list<array{key: string, groups: list<array{title: string, items: list<array{label: string, text: string, route: string, permission: ?string}>}>}> $configured */
        $configured = config('processes.areas', []);

        foreach ($configured as $area) {
            if ($area['key'] !== 'verwaltung') {
                continue;
            }

            foreach ($area['groups'] as $group) {
                $items = array_values(array_filter($group['items'], static fn (array $item): bool => $item['permission'] === null || Gate::allows($item['permission'])));

                if ($items !== []) {
                    $groups[] = ['title' => $group['title'], 'items' => $items];
                }
            }
        }

        return $groups;
    }
}
