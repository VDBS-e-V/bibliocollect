<?php

declare(strict_types=1);

use App\Foundation\Settings\SettingsRegistry;
use App\Foundation\Settings\SettingsRepository;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function rulesUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** Alle Felder mit den aktuellen Werten, einzelne überschrieben. */
function rulesForm(array $overrides = []): array
{
    $form = [];

    foreach (app(SettingsRegistry::class)->all() as $key => $definition) {
        $value = app(SettingsRepository::class)->current($key);
        $form[$definition['field']] = is_bool($value) ? ($value ? '1' : '0') : $value;
    }

    foreach ($overrides as $key => $value) {
        $form[str_replace('.', '__', $key)] = $value;
    }

    return $form;
}

function rulesCopy(string $barcode): Copy
{
    $title = Title::query()->create(['preferred_title' => 'Regelbuch '.$barcode, 'sort_title' => 'Regelbuch '.$barcode]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active]);
}

function rulesPatron(string $number): Patron
{
    return Patron::query()->create(['library_number' => $number, 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Rita', 'last_name' => 'Regel', 'birth_date' => '2010-01-01']);
}

beforeEach(function (): void {
    foreach (range(1, 7) as $day) {
        LibraryOpeningHour::query()->create(['day_of_week' => $day, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00']);
    }
});

it('shows the rules page only to the administration with the permission', function (): void {
    $this->actingAs(rulesUser('management'))->get(route('administration.rules.index'))->assertOk()->assertSee('Leihfrist Schüler:innen')->assertSee('Offene Vormerkungen je Person')->assertSee('Standard: 14');

    foreach (['staff', 'technical_admin', 'student_ag_extended', 'teacher'] as $role) {
        $this->actingAs(rulesUser($role))->get(route('administration.rules.index'))->assertForbidden();
        $this->actingAs(rulesUser($role))->put(route('administration.rules.update'), rulesForm())->assertForbidden();
    }
});

it('applies changed rules at once and keeps them in the database', function (): void {
    $admin = rulesUser('management');

    $this->actingAs($admin)->put(route('administration.rules.update'), rulesForm([
        'circulation.default_loan_period_days' => 21,
        'circulation.max_open_reservations' => 2,
        'circulation.allow_overdue_renewal' => '1',
        'circulation.renewal_period_days' => '',
    ]))->assertRedirect(route('administration.rules.index'))->assertSessionHas('rules_success');

    expect((int) config('circulation.default_loan_period_days'))->toBe(21)
        ->and((int) config('circulation.max_open_reservations'))->toBe(2)
        ->and(config('circulation.allow_overdue_renewal'))->toBeTrue()
        ->and(DB::table('app_settings')->count())->toBe(3);

    // Die Regel wirkt auf neue Ausleihen: 21 statt 14 Tage.
    $loan = app(CheckoutCopyAction::class)->execute(rulesPatron('300001'), rulesCopy('0090001')->barcode, $admin);
    expect((int) $loan->checked_out_at->copy()->startOfDay()->diffInDays($loan->due_on->copy()->startOfDay()))->toBeGreaterThanOrEqual(21);

    // Beim nächsten Start der Anwendung gelten die gespeicherten Werte wieder.
    config(['circulation.default_loan_period_days' => 14, 'circulation.max_open_reservations' => 5, 'circulation.allow_overdue_renewal' => false]);
    Cache::flush();
    (new SettingsRepository(app(SettingsRegistry::class)))->apply();

    expect((int) config('circulation.default_loan_period_days'))->toBe(21)->and((int) config('circulation.max_open_reservations'))->toBe(2);

    expect(AuditEvent::query()->where('action', 'settings.updated')->count())->toBe(1);
});

it('drops the stored value when it equals the default again and resets everything on request', function (): void {
    $admin = rulesUser('management');

    $this->actingAs($admin)->put(route('administration.rules.update'), rulesForm(['circulation.max_renewals' => 5]));
    expect(DB::table('app_settings')->where('key', 'circulation.max_renewals')->exists())->toBeTrue();

    $this->put(route('administration.rules.update'), rulesForm(['circulation.max_renewals' => 2]));
    expect(DB::table('app_settings')->count())->toBe(0)->and((int) config('circulation.max_renewals'))->toBe(2);

    $this->put(route('administration.rules.update'), rulesForm(['circulation.max_renewals' => 4, 'reminders.due_soon_days' => 5]));
    expect(DB::table('app_settings')->count())->toBe(2);

    $this->post(route('administration.rules.reset'))->assertRedirect(route('administration.rules.index'));
    expect(DB::table('app_settings')->count())->toBe(0)
        ->and((int) config('circulation.max_renewals'))->toBe(2)
        ->and((int) config('reminders.due_soon_days'))->toBe(2)
        ->and(AuditEvent::query()->where('action', 'settings.reset')->count())->toBe(1);
});

it('rejects values outside the allowed range', function (): void {
    $this->actingAs(rulesUser('management'));

    $this->put(route('administration.rules.update'), rulesForm(['circulation.default_loan_period_days' => 0]))->assertSessionHasErrors('circulation__default_loan_period_days');
    $this->put(route('administration.rules.update'), rulesForm(['circulation.max_open_reservations' => 99]))->assertSessionHasErrors('circulation__max_open_reservations');
    $this->put(route('administration.rules.update'), rulesForm(['circulation.max_renewals' => 'viele']))->assertSessionHasErrors('circulation__max_renewals');
    $this->put(route('administration.rules.update'), rulesForm(['circulation.default_loan_period_days' => '']))->assertSessionHasErrors('circulation__default_loan_period_days');

    expect(DB::table('app_settings')->count())->toBe(0);
});

it('turns reservations off with a limit of zero and honours a higher limit', function (): void {
    $admin = rulesUser('management');
    $holder = rulesPatron('300002');
    $waiter = rulesPatron('300003');
    $copy = rulesCopy('0090002');
    app(CheckoutCopyAction::class)->execute($holder, $copy->barcode, $admin);

    $this->actingAs($admin)->put(route('administration.rules.update'), rulesForm(['circulation.max_open_reservations' => 0]))->assertSessionHasNoErrors();

    expect(fn () => app(PlaceReservationAction::class)->execute($waiter, $copy->barcode, $admin))->toThrow(CirculationRuleViolation::class, 'ausgeschaltet');

    $this->put(route('administration.rules.update'), rulesForm(['circulation.max_open_reservations' => 3]));

    expect(app(PlaceReservationAction::class)->execute($waiter, $copy->barcode, $admin)->patron_id)->toBe((string) $waiter->getKey());
});

it('links the rules page in the administration navigation and the process list', function (): void {
    $this->actingAs(rulesUser('management'))->get(route('administration.home'))->assertOk()->assertSee('Regeln');
    $this->actingAs(rulesUser('management'))->get(route('pos.processes'))->assertOk()->assertSee('Regeln der Bibliothek');
});
