<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function startUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('shows real opening hours, closures and the current lending rules on the public start page', function (): void {
    LibraryOpeningHour::query()->create(['day_of_week' => 2, 'is_open' => true, 'opens_at' => '09:30', 'closes_at' => '13:00']);
    LibraryOpeningHour::query()->create(['day_of_week' => 3, 'is_open' => false, 'opens_at' => null, 'closes_at' => null]);
    LibraryClosure::query()->create(['date' => now()->addDays(5)->toDateString(), 'reason' => 'Herbstferien']);
    config(['circulation.default_loan_period_days' => 21, 'circulation.max_open_loans.default' => 4]);

    $this->get(route('public.home'))
        ->assertOk()
        ->assertSee('Dienstag')
        ->assertSee('09:30 bis 13:00 Uhr')
        ->assertDontSee('Mittwoch')
        ->assertSee('Herbstferien')
        ->assertSee('bis zu 4 Medien')
        ->assertSee('für 21 Tage')
        ->assertSee('Buchwunsch abgeben')
        ->assertSee('Onlinekonto aktivieren')
        ->assertSee('Anmelden')
        ->assertDontSee('später')
        ->assertDontSee('ab T2');
});

it('links the account button of the start page to the login and to the portal for signed in people', function (): void {
    $this->get(route('public.home'))->assertSee(route('login'), false);

    $this->actingAs(startUser('student'))->get(route('public.home'))->assertOk()->assertSee(route('portal.home'), false)->assertDontSee('Onlinekonto aktivieren');
});

it('shows the launch checklist and the admin tasks on the administration start page', function (): void {
    $admin = startUser('management');

    $page = $this->actingAs($admin)->get(route('administration.home'))->assertOk();
    $page->assertSee('Startklar?')->assertSee('Schuljahr und Klassen')->assertSee('Impressum, Datenschutz, Barrierefreiheit')->assertSee('Offen')->assertSee('Benutzerkonten verwalten')->assertSee('Regeln der Bibliothek');
    $page->assertDontSee('später')->assertDontSee('T8');

    SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    LibraryOpeningHour::query()->create(['day_of_week' => 1, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);

    $html = $this->get(route('administration.home'))->getContent();
    expect(substr_count($html, 'Erledigt'))->toBeGreaterThanOrEqual(1);
});

it('shows only the tasks a technical administrator may use and the system state', function (): void {
    $this->actingAs(startUser('technical_admin'))->get(route('administration.home'))
        ->assertOk()
        ->assertSee('Systemzustand')
        ->assertDontSee('Benutzerkonten verwalten');
});
