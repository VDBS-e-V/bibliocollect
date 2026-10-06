<?php

declare(strict_types=1);

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @param list<CopyStatus> $statuses */
function availabilityTitle(string $name, array $statuses): array
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copies = [];

    foreach ($statuses as $index => $status) {
        $copies[] = Copy::query()->create([
            'edition_id' => $edition->getKey(),
            'barcode' => strtoupper(substr(md5($name), 0, 6)).'-'.$index,
            'status' => $status,
        ]);
    }

    return [$title, $edition, $copies];
}

function availabilityLoan(Copy $copy, string $dueOn = '2026-11-01', bool $returned = false): Loan
{
    static $number = 0;
    $number++;

    $patron = Patron::query()->create([
        'library_number' => 'S-AV-'.$number,
        'kind' => PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Verfügbarkeit',
        'last_name' => 'Test'.$number,
        'birth_date' => '2010-01-01',
    ]);

    return Loan::query()->create([
        'patron_id' => $patron->getKey(),
        'copy_id' => $copy->getKey(),
        'checked_out_at' => now(),
        'due_on' => $dueOn,
        'returned_at' => $returned ? now() : null,
    ]);
}

it('derives availability from open loans of active copies only', function (): void {
    [$title, $edition, $copies] = availabilityTitle('Verfügbarkeitsbuch', [CopyStatus::Active, CopyStatus::Active, CopyStatus::Damaged]);

    availabilityLoan($copies[0], '2026-11-03');
    availabilityLoan($copies[2], '2026-11-01'); // beschädigtes Exemplar: zählt nicht

    $service = app(CopyAvailabilityService::class);
    $byTitle = $service->forTitles([(string) $title->getKey(), 'unbekannt'])[(string) $title->getKey()];
    $byEdition = $service->forEditions([(string) $edition->getKey()])[(string) $edition->getKey()];

    expect($byTitle->activeCopies)->toBe(2)
        ->and($byTitle->loanedCopies)->toBe(1)
        ->and($byTitle->availableCopies())->toBe(1)
        ->and($byTitle->isAvailable())->toBeTrue()
        ->and($byEdition->availableCopies())->toBe(1)
        ->and($service->forTitles(['unbekannt'])['unbekannt']->hasActiveCopies())->toBeFalse();
});

it('does not count returned loans', function (): void {
    [$title, , $copies] = availabilityTitle('Zurückgegeben', [CopyStatus::Active]);

    availabilityLoan($copies[0], returned: true);

    $availability = app(CopyAvailabilityService::class)->forTitles([(string) $title->getKey()])[(string) $title->getKey()];

    expect($availability->isAvailable())->toBeTrue()
        ->and($availability->earliestDueOn)->toBeNull();
});

it('shows availability on the public result list and title page', function (): void {
    [$free, , $freeCopies] = availabilityTitle('Freies Buch', [CopyStatus::Active, CopyStatus::Active]);
    [$loaned, , $loanedCopies] = availabilityTitle('Verliehenes Buch', [CopyStatus::Active]);

    availabilityLoan($freeCopies[0]);
    availabilityLoan($loanedCopies[0], '2026-12-24');

    $this->get(route('public.catalog.index'))
        ->assertOk()
        ->assertSee('1 von 2 Exemplaren verfügbar')
        ->assertSee('Derzeit ausgeliehen')
        ->assertSee('Frühestens zurück am 24.12.2026');

    $this->get(route('public.catalog.show', $loaned->getKey()))
        ->assertOk()
        ->assertSee('Derzeit ausgeliehen')
        ->assertSee('Frühestens zurück am 24.12.2026');

    $this->get(route('public.catalog.show', $free->getKey()))
        ->assertOk()
        ->assertSee('1 von 2 Exemplaren verfügbar');
});

it('never exposes patrons, barcodes or loan ids on public pages', function (): void {
    [$title, , $copies] = availabilityTitle('Datenschutzbuch', [CopyStatus::Active]);
    $loan = availabilityLoan($copies[0]);

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertDontSee($copies[0]->barcode)
        ->assertDontSee((string) $loan->getKey())
        ->assertDontSee($loan->patron->library_number)
        ->assertDontSee('Test');
});

it('lists each active copy with location and loan state but without barcodes', function (): void {
    [$title, , $copies] = availabilityTitle('Exemplarbuch', [CopyStatus::Active, CopyStatus::Active, CopyStatus::Lost]);

    $copies[0]->forceFill(['shelf_location' => 'Regal A'])->save();
    $copies[1]->forceFill(['shelf_location' => 'Regal B'])->save();
    availabilityLoan($copies[0], '2026-11-20');

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertSee('2 Exemplare, 1 verfügbar')
        ->assertSee('Regal A')
        ->assertSee('Regal B')
        ->assertSee('Ausgeliehen bis 20.11.2026')
        ->assertSee('Verfügbar')
        ->assertDontSee($copies[0]->barcode)
        ->assertDontSee($copies[1]->barcode);
});

it('shows the summary prominently and offers page navigation in the sidebar', function (): void {
    [$title, $edition] = availabilityTitle('Zusammenfassungsbuch', [CopyStatus::Active]);
    $edition->forceFill(['summary' => 'Eine spannende Geschichte über eine Bibliothek.', 'publisher_name' => 'Testverlag', 'publication_year' => 2020])->save();

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertSee('id="zusammenfassung"', false)
        ->assertSee('Eine spannende Geschichte über eine Bibliothek.')
        ->assertSee('Auf dieser Seite')
        ->assertSee('href="#standorte"', false)
        ->assertSee('Auf einen Blick')
        ->assertSee('Testverlag');

    [$bare] = availabilityTitle('Ohne Zusammenfassung', [CopyStatus::Active]);

    $this->get(route('public.catalog.show', $bare->getKey()))
        ->assertOk()
        ->assertDontSee('id="zusammenfassung"', false);
});

it('does not show placeholder texts of the national library as summary', function (): void {
    [$title, $edition] = availabilityTitle('Platzhalterbuch', [CopyStatus::Active]);
    $edition->forceFill(['summary' => 'Zu diesem Buch gibt es aktuell noch keine Inhaltsangabe. Falls Sie eine haben, senden Sie sie uns.'])->save();

    $this->get(route('public.catalog.show', $title->getKey()))
        ->assertOk()
        ->assertDontSee('id="zusammenfassung"', false)
        ->assertDontSee('keine Inhaltsangabe');
});
