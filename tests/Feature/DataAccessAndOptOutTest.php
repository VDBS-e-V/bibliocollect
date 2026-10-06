<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Reminders\Notifications\LibraryReminder;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function accessUser(string $role, ?Patron $patron = null): User
{
    $user = User::factory()->create(['email_verified_at' => now(), 'patron_id' => $patron?->getKey()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function accessPatron(string $number, string $last = 'Auskunft'): Patron
{
    return Patron::query()->create([
        'library_number' => $number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Datenauskunft',
        'last_name' => $last,
        'birth_date' => '2012-04-03',
        'email' => $number.'@example.invalid',
    ]);
}

function accessCopy(): Copy
{
    $title = Title::query()->create(['preferred_title' => 'Auskunftsbuch', 'sort_title' => 'Auskunftsbuch']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => 'AU-001', 'status' => CopyStatus::Active]);
}

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 10:00:00', 'Europe/Berlin'));

    foreach (range(1, 5) as $dayOfWeek) {
        LibraryOpeningHour::query()->create(['day_of_week' => $dayOfWeek, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '15:00']);
    }
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('shows staff everything stored about a patron and logs the access', function (): void {
    $staff = accessUser('staff');
    $patron = accessPatron('S-AU-1');
    app(CheckoutCopyAction::class)->execute($patron, accessCopy()->barcode, $staff);

    $this->actingAs($staff)->get(route('pos.patrons.data-export', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertSee('Auskunft über gespeicherte Daten')
        ->assertSee('S-AU-1')
        ->assertSee('2012-04-03')
        ->assertSee('Auskunftsbuch')
        ->assertSee('AU-001');

    $download = $this->actingAs($staff)->get(route('pos.patrons.data-export.download', ['patronId' => $patron->getKey()]))->assertOk();
    $json = json_decode($download->streamedContent(), true);

    expect($json['ausleihkonto']['nachname'])->toBe('Auskunft')
        ->and($json['ausleihen'])->toHaveCount(1)
        ->and($json['ausleihen'][0]['titel'])->toBe('Auskunftsbuch')
        ->and(AuditEvent::query()->whereIn('action', ['privacy.export.viewed', 'privacy.export.downloaded'])->count())->toBe(2);
});

it('keeps the staff export away from roles without sensitive access', function (string $role): void {
    $patron = accessPatron('S-AU-1');

    $this->actingAs(accessUser($role))->get(route('pos.patrons.data-export', ['patronId' => $patron->getKey()]))->assertForbidden();
    $this->actingAs(accessUser($role))->get(route('pos.patrons.data-export.download', ['patronId' => $patron->getKey()]))->assertForbidden();
})->with(['student_ag_basic', 'student_ag_extended', 'technical_admin', 'student']);

it('lets a person download only their own data from the portal', function (): void {
    $mine = accessPatron('S-AU-1', 'Eigene');
    accessPatron('S-AU-2', 'Fremde');
    $user = accessUser('student', $mine);

    $json = json_decode($this->actingAs($user)->get(route('portal.my-data'))->assertOk()->streamedContent(), true);

    expect($json['ausleihkonto']['nachname'])->toBe('Eigene')
        ->and($json['onlinekonto']['email'])->toBe($user->email)
        ->and(json_encode($json))->not->toContain('Fremde');

    $this->actingAs(accessUser('student'))->get(route('portal.my-data'))->assertForbidden();
});

it('stops reminder mails once a person switches them off in the portal', function (): void {
    Notification::fake();

    $patron = accessPatron('S-AU-1');
    $user = accessUser('student', $patron);
    $loan = app(CheckoutCopyAction::class)->execute($patron, accessCopy()->barcode, accessUser('staff'));
    $loan->forceFill(['due_on' => '2026-10-06'])->save();

    $this->actingAs($user)->post(route('portal.settings'), ['reminders_enabled' => '0'])
        ->assertRedirect(route('portal.home'))
        ->assertSessionHas('portal_success');

    expect($user->fresh()->reminders_enabled)->toBeFalse();

    $this->artisan('reminders:send')->expectsOutput('0 Erinnerung(en) verschickt.');
    Notification::assertNothingSent();

    $this->actingAs($user)->post(route('portal.settings'), ['reminders_enabled' => '1']);
    $this->artisan('reminders:send')->expectsOutput('1 Erinnerung(en) verschickt.');
    Notification::assertSentTo($user, LibraryReminder::class);

    $this->actingAs($user)->get(route('portal.home'))->assertOk()->assertSee('Erinnerungen per E-Mail')->assertSee('Meine gespeicherten Daten herunterladen');
});
