<?php

declare(strict_types=1);

namespace App\Foundation\Settings;

/**
 * Die Regeln, die die Verwaltung im Web ändern darf (Seite „Regeln“). Jede Einstellung überschreibt den gleichnamigen Wert
 * aus `config/circulation.php` bzw. `config/reminders.php`; ohne Eintrag gilt der Wert aus der Datei.
 */
final class SettingsRegistry
{
    public const INT = 'int';

    public const BOOL = 'bool';

    /**
     * @return list<array{title: string, lead: string, items: list<array{key: string, field: string, label: string, type: string, min: int, max: int, nullable: bool, hint: string}>}>
     */
    public function groups(): array
    {
        return [
            [
                'title' => 'Leihfristen',
                'lead' => 'Wie lange ein Medium ausgeliehen werden darf. Fällt der letzte Tag auf einen Schließtag, gilt der nächste Öffnungstag.',
                'items' => [
                    $this->int('circulation.default_loan_period_days', 'Leihfrist Schüler:innen (Tage)', 1, 180),
                    $this->int('circulation.loan_periods.by_kind.teacher', 'Leihfrist Lehrkräfte (Tage)', 1, 365),
                    $this->int('circulation.loan_periods.by_kind.employee', 'Leihfrist Mitarbeiter:innen (Tage)', 1, 365),
                ],
            ],
            [
                'title' => 'Höchstzahl gleichzeitiger Ausleihen',
                'lead' => '0 bedeutet unbegrenzt.',
                'items' => [
                    $this->int('circulation.max_open_loans.default', 'Schüler:innen', 0, 100, hint: '0 = unbegrenzt'),
                    $this->int('circulation.max_open_loans.by_kind.teacher', 'Lehrkräfte', 0, 100, hint: '0 = unbegrenzt'),
                    $this->int('circulation.max_open_loans.by_kind.employee', 'Mitarbeiter:innen', 0, 100, hint: '0 = unbegrenzt'),
                ],
            ],
            [
                'title' => 'Verlängern',
                'lead' => 'Verlängert wird nur, wenn niemand den Titel vorgemerkt hat.',
                'items' => [
                    $this->int('circulation.max_renewals', 'Verlängerungen je Ausleihe', 0, 10, hint: '0 = keine Verlängerung'),
                    $this->int('circulation.renewal_period_days', 'Dauer einer Verlängerung (Tage)', 1, 180, nullable: true, hint: 'Leer = so lang wie die Leihfrist'),
                    $this->bool('circulation.allow_overdue_renewal', 'Überfällige Ausleihen dürfen noch verlängert werden'),
                ],
            ],
            [
                'title' => 'Vormerken',
                'lead' => 'Eine Person kann mehrere Titel vormerken, jeden Titel aber nur einmal.',
                'items' => [
                    $this->int('circulation.max_open_reservations', 'Offene Vormerkungen je Person', 0, 50, hint: '0 = Vormerken ist ausgeschaltet'),
                    $this->int('circulation.reservation_pickup_days', 'Abholfrist für zurückgelegte Medien (Tage)', 1, 30),
                    $this->int('circulation.max_reservations_per_copy', 'Vormerkungen je Exemplar eines Titels', 1, 10, hint: 'Begrenzt die Warteschlange: 1 = nie mehr Vormerkungen als Exemplare'),
                    $this->int('circulation.reservation_buffer_days', 'Puffer für die Wartezeit einer Vormerkung (Tage)', 0, 60, hint: 'Wartezeit-Grenze = Leihfrist + eine Verlängerung + dieser Puffer'),
                ],
            ],
            [
                'title' => 'Belege',
                'lead' => 'Nach jedem Vorgang am Ausleihplatz gibt es einen Beleg. Er lässt sich immer drucken.',
                'items' => [
                    $this->bool('circulation.auto_receipt_mail', 'Beleg automatisch per E-Mail schicken, wenn am Ausleihkonto eine Adresse steht'),
                ],
            ],
            [
                'title' => 'Buchwünsche',
                'lead' => '',
                'items' => [
                    $this->int('circulation.max_open_wishes', 'Offene Buchwünsche je Person', 1, 20),
                ],
            ],
            [
                'title' => 'Erinnerungen per E-Mail',
                'lead' => 'Wirkt, sobald der Mailversand eingerichtet ist.',
                'items' => [
                    $this->int('reminders.due_soon_days', 'Erinnerung so viele Tage vor der Fälligkeit', 0, 14, hint: '0 = am Tag der Fälligkeit'),
                    $this->int('reminders.overdue_interval_days', 'Abstand der Erinnerungen bei Überfälligkeit (Tage)', 1, 60),
                ],
            ],
        ];
    }

    /** @return array<string, array{key: string, field: string, label: string, type: string, min: int, max: int, nullable: bool, hint: string}> nach Schlüssel */
    public function all(): array
    {
        $all = [];

        foreach ($this->groups() as $group) {
            foreach ($group['items'] as $item) {
                $all[$item['key']] = $item;
            }
        }

        return $all;
    }

    /** @return array{key: string, field: string, label: string, type: string, min: int, max: int, nullable: bool, hint: string} */
    private function int(string $key, string $label, int $min, int $max, bool $nullable = false, string $hint = ''): array
    {
        return ['key' => $key, 'field' => str_replace('.', '__', $key), 'label' => $label, 'type' => self::INT, 'min' => $min, 'max' => $max, 'nullable' => $nullable, 'hint' => $hint];
    }

    /** @return array{key: string, field: string, label: string, type: string, min: int, max: int, nullable: bool, hint: string} */
    private function bool(string $key, string $label): array
    {
        return ['key' => $key, 'field' => str_replace('.', '__', $key), 'label' => $label, 'type' => self::BOOL, 'min' => 0, 'max' => 1, 'nullable' => false, 'hint' => ''];
    }
}
