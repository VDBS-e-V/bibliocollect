<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Actions\IssuePatronLinkCodeAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Exceptions\PatronLinkCodeCannotBeIssued;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function workspacePatron(array $overrides = []): Patron
{
    return Patron::query()->create(array_merge([
        'library_number' => 'S-42001',
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Mika',
        'last_name' => 'Beispiel',
        'birth_date' => '2012-05-10',
        'email' => 'mika.private@example.test',
    ], $overrides));
}

function workspaceUserWithRole(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('lets student AG users perform basic patron lookup without exposing sensitive data', function (): void {
    $patron = workspacePatron();
    $agUser = workspaceUserWithRole('student_ag_basic');

    $this->actingAs($agUser)
        ->get(route('pos.patrons.index', ['q' => '42001']))
        ->assertOk()
        ->assertSee('Mika Beispiel')
        ->assertSee('S-42001');

    $this->actingAs($agUser)
        ->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertSee('Mika Beispiel')
        ->assertDontSee('mika.private@example.test')
        ->assertDontSee('10.05.2012')
        ->assertDontSee('Einmalcode ausgeben');
});


it('does not allow wildcard-only searches to become a patron directory', function (): void {
    workspacePatron();
    $agUser = workspaceUserWithRole('student_ag_basic');

    $this->actingAs($agUser)
        ->get(route('pos.patrons.index', ['q' => '%']))
        ->assertOk()
        ->assertDontSee('Mika Beispiel');
});

it('shows sensitive patron data only to staff with the dedicated permission', function (): void {
    $patron = workspacePatron();
    $staff = workspaceUserWithRole('staff');

    $this->actingAs($staff)
        ->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertOk()
        ->assertSee('mika.private@example.test')
        ->assertSee('10.05.2012')
        ->assertSee('Einmalcode ausgeben');
});

it('issues a one-time patron link code without storing the cleartext in flash data', function (): void {
    $patron = workspacePatron();
    $staff = workspaceUserWithRole('staff');

    $response = $this->actingAs($staff)
        ->post(route('pos.patrons.link-code.issue', ['patronId' => $patron->getKey()]));

    $response
        ->assertOk()
        ->assertSee('Einmalcode ausgegeben')
        ->assertHeader('Cache-Control', 'private, no-store, max-age=0')
        ->assertSessionMissing('issued_link_code');

    $this->assertDatabaseHas('patron_account_link_tokens', [
        'patron_id' => $patron->getKey(),
        'issued_by_user_id' => $staff->getKey(),
    ]);

    $fingerprint = (string) DB::table('patron_account_link_tokens')
        ->where('patron_id', $patron->getKey())
        ->value('fingerprint');

    expect($fingerprint)->toHaveLength(64)
        ->and($response->getContent())->not->toContain($fingerprint);
});

it('does not issue link codes for already linked or non-linkable patrons', function (): void {
    $staff = workspaceUserWithRole('staff');

    $linkedPatron = workspacePatron(['library_number' => 'S-42002']);
    User::factory()->create([
        'email_verified_at' => now(),
        'patron_id' => $linkedPatron->getKey(),
    ]);

    expect(fn () => app(IssuePatronLinkCodeAction::class)->execute($linkedPatron, $staff))
        ->toThrow(PatronLinkCodeCannotBeIssued::class);

    $employee = workspacePatron([
        'library_number' => 'M-42003',
        'kind' => PatronKind::Employee,
    ]);

    expect(fn () => app(IssuePatronLinkCodeAction::class)->execute($employee, $staff))
        ->toThrow(PatronLinkCodeCannotBeIssued::class);
});

it('lets staff manage only the student AG role bundles on linked online accounts', function (): void {
    $patron = workspacePatron();
    $target = User::factory()->create([
        'email_verified_at' => now(),
        'patron_id' => $patron->getKey(),
    ]);
    app(AssignRoleAction::class)->execute($target, 'student');

    $staff = workspaceUserWithRole('staff');

    $this->actingAs($staff)
        ->put(route('pos.patrons.ag-roles.store', [
            'patronId' => $patron->getKey(),
            'roleKey' => 'student_ag_extended',
        ]))
        ->assertRedirect();

    expect($target->fresh()->roleKeys())
        ->toContain('student', 'student_ag_extended');

    $this->actingAs($staff)
        ->delete(route('pos.patrons.ag-roles.destroy', [
            'patronId' => $patron->getKey(),
            'roleKey' => 'student_ag_extended',
        ]))
        ->assertRedirect();

    expect($target->fresh()->roleKeys())
        ->toContain('student')
        ->not->toContain('student_ag_extended');

    $this->actingAs($staff)
        ->put(route('pos.patrons.ag-roles.store', [
            'patronId' => $patron->getKey(),
            'roleKey' => 'staff',
        ]))
        ->assertNotFound();
});

it('keeps technical administration outside patron lookup', function (): void {
    $patron = workspacePatron();
    $admin = workspaceUserWithRole('technical_admin');

    $this->actingAs($admin)
        ->get(route('pos.patrons.show', ['patronId' => $patron->getKey()]))
        ->assertForbidden();
});
