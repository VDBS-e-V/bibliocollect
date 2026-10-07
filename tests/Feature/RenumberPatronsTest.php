<?php

declare(strict_types=1);

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function renumberPatron(string $number, string $last): Patron
{
    return Patron::query()->create(['library_number' => $number, 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Rena', 'last_name' => $last, 'birth_date' => '2010-01-01']);
}

it('only counts on a dry run', function (): void {
    renumberPatron('S-10001', 'Alt');

    $this->artisan('patrons:renumber', ['--dry-run' => true])->expectsOutputToContain('1 Bibliotheksnummern würden ersetzt')->assertSuccessful();

    expect(Patron::query()->where('library_number', 'S-10001')->exists())->toBeTrue();
});

it('replaces old numbers by random six-digit ones and keeps good and anonymised numbers', function (): void {
    $old = renumberPatron('S-10001', 'Alt');
    $teacher = renumberPatron('L-20001', 'Lehrkraft');
    $good = renumberPatron('482915', 'Gut');
    $anon = renumberPatron('ANON-01HXYZ', 'Anonym');

    $this->artisan('patrons:renumber', ['--yes' => true])->expectsOutputToContain('2 Bibliotheksnummern ersetzt')->assertSuccessful();

    expect($old->refresh()->library_number)->toMatch('/^[1-9]\d{5}$/')
        ->and($teacher->refresh()->library_number)->toMatch('/^[1-9]\d{5}$/')
        ->and($old->library_number)->not->toBe($teacher->library_number)
        ->and($good->refresh()->library_number)->toBe('482915')
        ->and($anon->refresh()->library_number)->toBe('ANON-01HXYZ')
        ->and(AuditEvent::query()->where('action', 'patrons.library_numbers.renumbered')->count())->toBe(1);

    // Ein zweiter Lauf hat nichts mehr zu tun.
    $this->artisan('patrons:renumber', ['--yes' => true])->expectsOutputToContain('folgen schon dem Schema')->assertSuccessful();

    $files = glob(storage_path('app/private/bibliotheksnummern-alt-neu-*.csv')) ?: [];
    expect($files)->not->toBeEmpty();
    expect(file_get_contents(end($files)))->toContain('S-10001;');

    foreach ($files as $file) {
        unlink($file);
    }
});

it('refuses to renumber without a terminal unless --yes is given', function (): void {
    renumberPatron('S-10001', 'Alt');

    $this->artisan('patrons:renumber', ['--no-interaction' => true])->assertFailed();

    expect(Patron::query()->where('library_number', 'S-10001')->exists())->toBeTrue();
});
