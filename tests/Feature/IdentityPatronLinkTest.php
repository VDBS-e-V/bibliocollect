<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Identity\Contracts\PatronLinkGateway;
use App\Modules\Identity\Exceptions\InvalidPatronLinkCode;
use App\Modules\Patrons\Actions\IssuePatronLinkCodeAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('links an online account to an existing patron with a one-time code', function (): void {
    Notification::fake();

    $patron = Patron::query()->create([
        'library_number' => '384917',
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Mika',
        'last_name' => 'Beispiel',
        'birth_date' => '2012-05-10',
        'email' => null,
    ]);

    $issued = app(IssuePatronLinkCodeAction::class)->execute($patron);

    $response = $this->post(route('identity.claim.store'), [
        'code' => $issued->code,
        'email' => 'mika@example.test',
        'password' => 'Bibliothek2026',
        'password_confirmation' => 'Bibliothek2026',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'mika@example.test')->firstOrFail();

    expect($user->patron_id)->toBe($patron->getKey())
        ->and($user->public_id)->not->toBeNull()
        ->and($user->roleKeys())->toBe(['student']);

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('never allows a patron link code to be consumed twice', function (): void {
    $patron = Patron::query()->create([
        'library_number' => '527164',
        'kind' => PatronKind::Teacher,
        'status' => PatronStatus::Active,
        'first_name' => 'Alex',
        'last_name' => 'Lehrkraft',
        'birth_date' => '1980-01-01',
    ]);

    $issued = app(IssuePatronLinkCodeAction::class)->execute($patron);
    $gateway = app(PatronLinkGateway::class);

    DB::transaction(fn () => $gateway->consume($issued->code));

    expect(fn () => DB::transaction(fn () => $gateway->consume($issued->code)))
        ->toThrow(InvalidPatronLinkCode::class);
});

it('derives permissions from persisted combinable roles', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, 'student_ag_basic');

    expect($user->allowsPermission('surface.portal.access'))->toBeTrue()
        ->and($user->allowsPermission('surface.pos.access'))->toBeTrue()
        ->and($user->allowsPermission('surface.administration.access'))->toBeFalse();

    $this->actingAs($user)->get('/betrieb')->assertOk();
    $this->actingAs($user)->get('/verwaltung')->assertForbidden();
});

it('keeps technical administrators independent from patron records', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'patron_id' => null,
    ]);
    app(AssignRoleAction::class)->execute($user, 'technical_admin');

    expect($user->patron_id)->toBeNull()
        ->and($user->allowsPermission('surface.administration.access'))->toBeTrue()
        ->and($user->allowsPermission('surface.portal.access'))->toBeFalse();

    $this->actingAs($user)->get('/verwaltung')->assertOk();
    $this->actingAs($user)->get('/konto')->assertForbidden();
});
