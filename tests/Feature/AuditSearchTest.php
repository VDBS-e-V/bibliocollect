<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function auditSearchAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now(), 'name' => 'Frau Verwaltung']);
    app(AssignRoleAction::class)->execute($user, 'management');

    return $user;
}

function auditSearchPatron(string $number, string $first, string $last): Patron
{
    return Patron::query()->create(['library_number' => $number, 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => $first, 'last_name' => $last, 'birth_date' => '2012-01-01']);
}

function auditSearchEvent(string $action, string $summary, array $attributes = []): AuditEvent
{
    return AuditEvent::query()->create(array_merge(['occurred_at' => now(), 'action' => $action, 'summary' => $summary], $attributes));
}

it('filters the protocol by event, period, account, person and text', function (): void {
    $admin = auditSearchAdmin();
    $mia = auditSearchPatron('111111', 'Mia', 'Müller');
    $ben = auditSearchPatron('222222', 'Ben', 'Schmidt');

    auditSearchEvent('circulation.loan.checked_out', 'Ausleihe Buch Eins', ['context' => ['patron_id' => (string) $mia->getKey()], 'actor_user_id' => $admin->id, 'occurred_at' => '2026-10-01 10:00:00']);
    auditSearchEvent('circulation.loan.returned', 'Rückgabe Buch Zwei', ['context' => ['patron_id' => (string) $ben->getKey()], 'occurred_at' => '2026-10-05 10:00:00']);
    auditSearchEvent('patrons.anonymized', 'Konto anonymisiert', ['subject_type' => Patron::class, 'subject_id' => (string) $mia->getKey(), 'occurred_at' => '2026-10-08 10:00:00']);

    $page = fn (array $query) => $this->actingAs($admin)->get(route('administration.audit.index', $query))->assertOk();

    $page([])->assertSee('Buch Eins')->assertSee('Buch Zwei')->assertSee('Konto anonymisiert');
    $page(['ereignis' => 'circulation.loan.returned'])->assertSee('Buch Zwei')->assertDontSee('Buch Eins');
    $page(['von' => '2026-10-04', 'bis' => '2026-10-06'])->assertSee('Buch Zwei')->assertDontSee('Buch Eins')->assertDontSee('Konto anonymisiert');
    $page(['von_konto' => $admin->id])->assertSee('Buch Eins')->assertDontSee('Buch Zwei');

    // Person: nach Nachname, Vorname und Nummer, über Kontext und betroffenen Datensatz.
    $page(['person' => 'Müller'])->assertSee('Buch Eins')->assertSee('Konto anonymisiert')->assertDontSee('Buch Zwei')->assertSee('Müller, Mia (111111)');
    $page(['person' => 'mia müller'])->assertSee('Buch Eins')->assertDontSee('Buch Zwei');
    $page(['person' => '222222'])->assertSee('Buch Zwei')->assertDontSee('Buch Eins');
    $page(['person' => 'Niemand'])->assertSee('Keine Person gefunden')->assertSee('Keine Ereignisse gefunden');

    $page(['q' => 'Buch Zwei'])->assertSee('Buch Zwei')->assertDontSee('Buch Eins');
    $page(['bereich' => 'patrons'])->assertSee('Konto anonymisiert')->assertDontSee('Buch Eins')->assertSee('Ausleihkonten');
});

it('exports the filtered protocol as csv and records the export', function (): void {
    $admin = auditSearchAdmin();
    $mia = auditSearchPatron('111111', 'Mia', 'Müller');
    auditSearchEvent('circulation.loan.checked_out', '=Ausleihe Buch Eins', ['context' => ['patron_id' => (string) $mia->getKey()], 'actor_user_id' => $admin->id]);
    auditSearchEvent('circulation.loan.returned', 'Rückgabe Buch Zwei');

    $response = $this->actingAs($admin)->get(route('administration.audit.export', ['person' => 'Müller']))->assertOk();
    $csv = $response->streamedContent();

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($csv)->toContain('Zeitpunkt;Ereignis;Beschreibung')
        ->and($csv)->toContain("'=Ausleihe Buch Eins")
        ->and($csv)->toContain('Müller, Mia (111111)')
        ->and($csv)->toContain('Frau Verwaltung')
        ->and($csv)->not->toContain('Buch Zwei');

    expect(AuditEvent::query()->where('action', 'audit.exported')->count())->toBe(1);

    foreach (['staff', 'technical_admin', 'student_ag_extended'] as $role) {
        $other = User::factory()->create(['email_verified_at' => now()]);
        app(AssignRoleAction::class)->execute($other, $role);
        $this->actingAs($other)->get(route('administration.audit.export'))->assertForbidden();
    }
});
