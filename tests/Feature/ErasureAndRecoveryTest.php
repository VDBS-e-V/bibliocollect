<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Identity\Models\UserRoleAssignment;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/backups'));
});

function erasureUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function erasurePatron(PatronStatus $status = PatronStatus::Departed): Patron
{
    return Patron::query()->create([
        'library_number' => '520001', 'kind' => PatronKind::Student, 'status' => $status,
        'first_name' => 'Elli', 'last_name' => 'Entfernen', 'birth_date' => '2010-05-17', 'email' => 'elli@example.org', 'leaving_on' => '2026-09-30',
    ]);
}

it('anonymizes a departed library account at once on request', function (): void {
    $patron = erasurePatron();
    $account = User::factory()->create(['patron_id' => $patron->getKey(), 'email' => 'elli.konto@example.org', 'name' => 'Elli Entfernen']);
    $card = PatronCard::query()->create(['number' => '4000000001', 'batch' => 1, 'status' => CardStatus::Assigned, 'patron_id' => $patron->getKey(), 'assigned_at' => now()]);

    $this->actingAs(erasureUser('management'))->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))->assertOk()->assertSee('Daten jetzt anonymisieren');

    $this->post(route('pos.patrons.anonymize', ['patronId' => $patron->getKey()]), [])->assertSessionHasErrors('confirm_erase');

    $this->post(route('pos.patrons.anonymize', ['patronId' => $patron->getKey()]), ['confirm_erase' => '1'])
        ->assertRedirect(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertSessionHas('workspace_success');

    $patron->refresh();
    expect($patron->first_name)->toBe('Anonymisiert')
        ->and($patron->last_name)->toBe('Anonymisiert')
        ->and($patron->email)->toBeNull()
        ->and($patron->library_number)->toStartWith('ANON-')
        ->and($patron->birth_date->toDateString())->toBe('2010-01-01')
        ->and($account->refresh()->email)->toEndWith('@anonym.invalid')
        ->and($account->patron_id)->toBeNull()
        ->and($card->refresh()->patron_id)->toBeNull()
        ->and($card->status)->toBe(CardStatus::Blocked)
        ->and(AuditEvent::query()->where('action', 'privacy.patron.erased')->count())->toBe(1);

    // Ein zweites Mal geht nicht.
    $this->post(route('pos.patrons.anonymize', ['patronId' => $patron->getKey()]), ['confirm_erase' => '1'])->assertSessionHas('workspace_error');
});

it('refuses to anonymize active accounts and hides the function from other roles', function (): void {
    $active = erasurePatron(PatronStatus::Active);

    $this->actingAs(erasureUser('management'))->post(route('pos.patrons.anonymize', ['patronId' => $active->getKey()]), ['confirm_erase' => '1'])->assertSessionHas('workspace_error');
    expect($active->refresh()->first_name)->toBe('Elli');

    $this->get(route('pos.patrons.show', ['patronId' => $active->getKey()]))->assertDontSee('Daten jetzt anonymisieren');

    foreach (['staff', 'student_ag_extended', 'teacher'] as $role) {
        $this->actingAs(erasureUser($role))->post(route('pos.patrons.anonymize', ['patronId' => $active->getKey()]), ['confirm_erase' => '1'])->assertForbidden();
    }
});

it('does not change the school year when the safety backup before the transition fails', function (): void {
    // Im Test läuft die Datenbank im Speicher innerhalb einer Transaktion: Die Sicherung scheitert, und der Wechsel bleibt aus.
    config(['foundation.backup_before_transition' => true]);

    $from = SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    $target = SchoolYear::query()->create(['name' => '2027/28', 'starts_on' => '2027-08-01', 'ends_on' => '2028-07-31', 'is_active' => false]);

    $this->actingAs(erasureUser('management'))->post(route('administration.transition.commit'), [
        'from_id' => (string) $from->getKey(), 'target_id' => (string) $target->getKey(), 'mapping' => ['a' => 'b'], 'confirm' => '1',
    ])->assertRedirect()->assertSessionHas('school_error', static fn (string $text): bool => str_contains($text, 'Sicherung'));

    expect($from->refresh()->is_active)->toBeTrue()->and($target->refresh()->is_active)->toBeFalse();
})->skip(fn (): bool => DB::connection()->getDriverName() !== 'sqlite', 'Der Fehlerfall der Sicherung lässt sich nur mit SQLite im Speicher erzwingen.');

it('restores access of a locked-out administration account via the setup page', function (): void {
    config(['hosting.setup_token' => str_repeat('s', 30)]);

    // Die Routen werden beim Start geladen; für den Test erneut registrieren.
    app('router')->setRoutes(new RouteCollection);
    require base_path('routes/maintenance.php');
    app('router')->getRoutes()->refreshNameLookups();
    $locked = erasureUser('management');
    $locked->forceFill(['disabled_at' => now(), 'disabled_reason' => 'manual', 'email' => 'verwaltung@example.org'])->save();
    UserRoleAssignment::query()->where('user_id', $locked->getKey())->delete();

    $this->post('/_setup/recover', ['token' => 'falsch', 'email' => 'verwaltung@example.org', 'password' => 'ein-langes-passwort-1'])->assertForbidden();
    $this->post('/_setup/recover', ['token' => str_repeat('s', 30), 'email' => 'verwaltung@example.org', 'password' => 'kurz'])->assertStatus(422);
    $this->post('/_setup/recover', ['token' => str_repeat('s', 30), 'email' => 'gibts@example.org', 'password' => 'ein-langes-passwort-1'])->assertNotFound();

    $this->post('/_setup/recover', ['token' => str_repeat('s', 30), 'email' => 'verwaltung@example.org', 'password' => 'ein-langes-passwort-1'])->assertOk()->assertSee('wiederhergestellt');

    $locked->refresh();
    expect($locked->disabled_at)->toBeNull()
        ->and(Hash::check('ein-langes-passwort-1', $locked->password))->toBeTrue()
        ->and(UserRoleAssignment::query()->where('user_id', $locked->getKey())->where('role_key', 'management')->exists())->toBeTrue();
});
