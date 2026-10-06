<?php

declare(strict_types=1);

use App\Foundation\Support\Code128Svg;
use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function labelUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function labelCopy(string $barcode, string $title = 'Etikettenbuch'): Copy
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => 'book']);

    return Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => $barcode, 'status' => CopyStatus::Active, 'shelf_location' => 'J 5 TEST']);
}

it('builds well-formed code 128 symbols with the right checksum', function (): void {
    // "A": Start B (104) + Wert 33 + Prüfzeichen (104 + 33) mod 103 = 34 + Stopp
    expect(Code128Svg::symbols('A'))->toBe([104, 33, 34, 106]);

    $reflection = new ReflectionClass(Code128Svg::class);
    $patterns = $reflection->getConstant('PATTERNS');

    expect($patterns)->toHaveCount(107)
        ->and(count(array_unique($patterns)))->toBe(107)
        ->and($patterns[104])->toBe('211214')
        ->and($patterns[106])->toBe('2331112');

    foreach ($patterns as $value => $pattern) {
        expect(array_sum(array_map('intval', str_split($pattern))))->toBe($value === 106 ? 13 : 11);
    }
});

it('renders a scannable svg and rejects unsupported text', function (): void {
    $svg = Code128Svg::render('BC-MOMO-001');

    expect($svg)->toStartWith('<svg')
        ->and($svg)->toContain('aria-label="Strichcode BC-MOMO-001"')
        ->and(substr_count($svg, '<rect'))->toBeGreaterThan(30)
        ->and(fn () => Code128Svg::render('Größe'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Code128Svg::render(''))->toThrow(InvalidArgumentException::class);
});

it('lists copies and prints a label sheet that respects the first free position', function (): void {
    $staff = labelUser('staff');
    labelCopy('LB-001');
    labelCopy('LB-002', 'Zweites Buch');

    $this->actingAs($staff)->get(route('pos.labels.copies'))->assertOk()->assertSee('LB-001')->assertSee('LB-002');
    $this->actingAs($staff)->get(route('pos.labels.copies', ['q' => 'Zweites']))->assertOk()->assertSee('LB-002')->assertDontSee('LB-001');

    $ids = Copy::query()->orderBy('barcode')->pluck('id')->all();

    $html = $this->actingAs($staff)->post(route('pos.labels.copies.print'), ['copies' => $ids, 'start' => 4])->assertOk()->getContent();

    expect(substr_count($html, 'class="label empty"'))->toBe(3)
        ->and(substr_count($html, '<svg'))->toBe(2)
        ->and($html)->toContain('J 5 TEST')
        ->and($html)->toContain('Strichcode LB-001');

    $this->actingAs($staff)->post(route('pos.labels.copies.print'), ['copies' => []])->assertSessionHasErrors('copies');
});

it('prints library cards for active patrons only', function (): void {
    $staff = labelUser('staff');

    $active = Patron::query()->create(['library_number' => 'S-LB-1', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Ausweis', 'last_name' => 'Aktiv', 'birth_date' => '2012-01-01']);
    $departed = Patron::query()->create(['library_number' => 'S-LB-2', 'kind' => PatronKind::Student, 'status' => PatronStatus::Departed, 'first_name' => 'Ausweis', 'last_name' => 'Weg', 'birth_date' => '2012-01-01', 'leaving_on' => '2026-01-01']);

    $this->actingAs($staff)->get(route('pos.labels.cards'))->assertOk()->assertSee('Aktiv')->assertDontSee('Weg,');

    $html = $this->actingAs($staff)
        ->post(route('pos.labels.cards.print'), ['patrons' => [(string) $active->getKey(), (string) $departed->getKey()]])
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Ausweis Aktiv')->not->toContain('Ausweis Weg')->toContain('Strichcode S-LB-1');
});

it('keeps labels and cards away from roles without the matching permission', function (): void {
    $this->actingAs(labelUser('student_ag_basic'))->get(route('pos.labels.copies'))->assertForbidden();
    $this->actingAs(labelUser('student_ag_extended'))->get(route('pos.labels.copies'))->assertOk();
    $this->actingAs(labelUser('student_ag_extended'))->get(route('pos.labels.cards'))->assertForbidden();
    $this->actingAs(labelUser('staff'))->get(route('pos.labels.cards'))->assertOk();
    $this->actingAs(labelUser('technical_admin'))->get(route('pos.labels.copies'))->assertForbidden();
});
