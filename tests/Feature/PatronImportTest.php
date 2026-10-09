<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Actions\ImportPatronsAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Import\PatronCsvParser;
use App\Modules\Patrons\Import\PatronImportPlanner;
use App\Modules\Patrons\Import\XlsxTemplate;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function importUser(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function importSchool(): array
{
    $year = SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);
    $classes = [];

    foreach ([['5a', 5], ['6b', 6]] as [$name, $grade]) {
        $classes[$name] = SchoolClass::query()->create(['school_year_id' => $year->getKey(), 'name' => $name, 'grade_level' => $grade, 'is_active' => true]);
    }

    return [$year, $classes];
}

function importFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'patrons');
    file_put_contents($path, $contents);

    return $path;
}

function importRows(string $contents): array
{
    return app(PatronCsvParser::class)->parse(importFile($contents));
}

it('reads semicolon, comma and tab separated files with aliases, BOM and Windows-1252', function (): void {
    $semicolon = importRows("\xEF\xBB\xBFVorname;Nachname;Geburtsdatum\nMia;Müller;14.03.2014\n");
    $comma = importRows("Vorname,Familienname,Geboren,E-Mail\nMia,Meier,2014-03-14,mia@example.invalid\n");
    $tab = importRows("vorname\tnachname\tgeburtsdatum\nMia\tMeier\t14.03.2014\n");
    $windows = importRows(mb_convert_encoding("vorname;nachname;geburtsdatum\nJürgen;Größe;01.02.2013\n", 'Windows-1252', 'UTF-8'));

    expect($semicolon[0]['values']['nachname'])->toBe('Müller')
        ->and($semicolon[0]['line'])->toBe(2)
        ->and($comma[0]['values']['email'])->toBe('mia@example.invalid')
        ->and($tab[0]['values']['geburtsdatum'])->toBe('14.03.2014')
        ->and($windows[0]['values']['vorname'])->toBe('Jürgen')
        ->and($windows[0]['values']['nachname'])->toBe('Größe');
});

it('ignores columns that are not part of the class list format', function (): void {
    $rows = importRows("vorname;nachname;geburtsdatum;klasse;art;bibliotheksnummer\nMia;Meier;14.03.2014;9z;Lehrkraft;S-1\n");

    expect(array_keys($rows[0]['values']))->toBe(PatronCsvParser::COLUMNS)
        ->and(PatronCsvParser::COLUMNS)->toBe(['vorname', 'nachname', 'geburtsdatum', 'email', 'klasse']);
});

it('rejects files without required columns, data or with too many rows', function (): void {
    expect(fn () => importRows("vorname;nachname\nMia;Meier\n"))->toThrow(InvalidArgumentException::class, 'geburtsdatum')
        ->and(fn () => importRows("vorname;nachname;geburtsdatum\n"))->toThrow(InvalidArgumentException::class, 'keine Datenzeilen')
        ->and(fn () => importRows(''))->toThrow(InvalidArgumentException::class, 'leer');

    $big = "vorname;nachname;geburtsdatum\n".str_repeat("A;B;01.01.2012\n", PatronCsvParser::MAX_ROWS + 1);

    expect(fn () => importRows($big))->toThrow(InvalidArgumentException::class, 'mehr als');
});

it('classifies rows as new, existing, duplicate or error without writing', function (): void {
    [, $classes] = importSchool();
    Patron::query()->create(['library_number' => 'S-10001', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Mia', 'last_name' => 'Müller', 'birth_date' => '2014-03-14', 'school_class_id' => $classes['5a']->getKey()]);

    $plan = app(PatronImportPlanner::class)->plan(importRows(implode("\n", [
        'vorname;nachname;geburtsdatum;email',
        'Neu;Kind;01.02.2013;',                   // 2: neu
        'MIA;MÜLLER;14.03.2014;',                 // 3: schon vorhanden (Schreibweise egal)
        'Neu;Kind;2013-02-01;',                   // 4: doppelt in der Datei
        'Fehler;Datum;31.02.2013;',               // 5: ungültiges Datum
        'Fehler;Zukunft;01.02.2999;',             // 6: Zukunft
        'Fehler;Mail;01.02.2013;keine-mail',      // 7: ungültige E-Mail
        ';Ohne;01.02.2013;',                      // 8: Vorname fehlt
    ])."\n"), (string) $classes['6b']->getKey());

    $byLine = collect($plan['rows'])->keyBy('line');

    expect($byLine[2]['status'])->toBe('new')
        ->and($byLine[2]['class_name'])->toBe('6b')
        ->and($byLine[2]['kind'])->toBe(PatronKind::Student)
        ->and($byLine[3]['status'])->toBe('existing')
        ->and($byLine[4]['status'])->toBe('duplicate')
        ->and($byLine[5]['status'])->toBe('error')
        ->and($byLine[6]['status'])->toBe('error')
        ->and($byLine[7]['status'])->toBe('error')
        ->and($byLine[8]['status'])->toBe('error')
        ->and($plan['counts'])->toBe(['new' => 1, 'update' => 0, 'existing' => 1, 'duplicate' => 1, 'error' => 4])
        ->and(Patron::query()->count())->toBe(1);
});

it('treats every row as an error when the chosen class does not exist in the active year', function (): void {
    importSchool();

    $plan = app(PatronImportPlanner::class)->plan(importRows("vorname;nachname;geburtsdatum\nMia;Neu;14.03.2014\n"), 'gibt-es-nicht');

    expect($plan['counts'])->toBe(['new' => 0, 'update' => 0, 'existing' => 0, 'duplicate' => 0, 'error' => 1])
        ->and($plan['rows'][0]['messages'][0])->toContain('Klasse');
});

it('imports new students into the chosen class with random numbers and no card', function (): void {
    [, $classes] = importSchool();
    Patron::query()->create(['library_number' => 'S-10007', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Alt', 'last_name' => 'Bestand', 'birth_date' => '2010-01-01', 'school_class_id' => $classes['5a']->getKey()]);

    $result = app(ImportPatronsAction::class)->execute(importRows(implode("\n", [
        'vorname;nachname;geburtsdatum;email',
        'Mia;Neu;14.03.2014;mia@example.invalid',
        'Jonas;Neu;02.11.2012;',
        'Alt;Bestand;01.01.2010;',
    ])."\n"), (string) $classes['6b']->getKey(), importUser());

    $mia = Patron::query()->where('first_name', 'Mia')->where('last_name', 'Neu')->firstOrFail();
    $jonas = Patron::query()->where('first_name', 'Jonas')->firstOrFail();

    expect($result)->toBe(['created' => 2, 'updated' => 0, 'skipped' => 1])
        ->and($mia->kind)->toBe(PatronKind::Student)
        ->and($mia->library_number)->toMatch('/^[1-9]\d{5}$/')
        ->and($mia->library_number)->not->toBe($jonas->library_number)
        ->and($mia->school_class_id)->toBe((string) $classes['6b']->getKey())
        ->and($mia->email)->toBe('mia@example.invalid')
        ->and($jonas->school_class_id)->toBe((string) $classes['6b']->getKey())
        ->and(PatronCard::query()->count())->toBe(0)
        ->and(AuditEvent::query()->where('action', 'patrons.import.committed')->count())->toBe(1);
});

it('imports nothing when the file contains errors', function (): void {
    [, $classes] = importSchool();

    expect(fn () => app(ImportPatronsAction::class)->execute(importRows("vorname;nachname;geburtsdatum\nGut;Kind;01.02.2013\nSchlecht;Kind;31.02.2013\n"), (string) $classes['5a']->getKey(), importUser()))
        ->toThrow(InvalidArgumentException::class, 'Fehler');

    expect(Patron::query()->count())->toBe(0);
});

it('runs the upload, preview and commit workflow for staff with the class chosen on upload', function (): void {
    Storage::fake('local');
    [, $classes] = importSchool();
    $staff = importUser();

    $this->actingAs($staff)->get(route('pos.patrons.import.create'))->assertOk()->assertSee('Dateiformat')->assertSee('5a')->assertSee('6b');
    $this->actingAs($staff)->get(route('pos.patrons.import.template'))->assertOk()->assertDownload('klassenliste-vorlage.xlsx');
    $this->actingAs($staff)->get(route('pos.patrons.import.template-csv'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $upload = UploadedFile::fake()->createWithContent('schueler.csv', "vorname;nachname;geburtsdatum;email\nMia;Import;14.03.2014;\n");

    $this->actingAs($staff)->post(route('pos.patrons.import.store'), ['file' => $upload, 'school_class_id' => 'fremd'])->assertSessionHasErrors('school_class_id');

    $response = $this->actingAs($staff)->post(route('pos.patrons.import.store'), ['file' => $upload, 'school_class_id' => (string) $classes['6b']->getKey()]);
    $response->assertRedirect();
    $url = $response->headers->get('Location');

    $this->actingAs($staff)->get($url)->assertOk()->assertSee('Mia Import')->assertSee('Wird angelegt')->assertSee('Klasse 6b');

    expect(Patron::query()->count())->toBe(0);

    $this->actingAs($staff)->post($url, [])->assertSessionHasErrors('confirm');

    $this->actingAs($staff)->post($url, ['confirm' => '1'])
        ->assertRedirect(route('pos.patrons.index'))
        ->assertSessionHas('workspace_success');

    $patron = Patron::query()->where('last_name', 'Import')->firstOrFail();
    expect($patron->school_class_id)->toBe((string) $classes['6b']->getKey());

    $this->actingAs($staff)->get($url)->assertNotFound();
});

it('blocks the commit page for files with errors and rejects bad tokens', function (): void {
    Storage::fake('local');
    [, $classes] = importSchool();
    $staff = importUser();

    $response = $this->actingAs($staff)->post(route('pos.patrons.import.store'), [
        'school_class_id' => (string) $classes['5a']->getKey(),
        'file' => UploadedFile::fake()->createWithContent('x.csv', "vorname;nachname;geburtsdatum\nFehler;Kind;31.02.2013\n"),
    ]);

    $this->actingAs($staff)->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee('Import gesperrt')
        ->assertDontSee('Ausleihkonten anlegen');

    $this->actingAs($staff)->get(route('pos.patrons.import.show', ['token' => '../../etc/passwd']))->assertNotFound();
    $this->actingAs($staff)->get(route('pos.patrons.import.show', ['token' => str_repeat('a', 26)]))->assertNotFound();
});

it('keeps the import away from users without patron management rights', function (string $role): void {
    $user = importUser($role);

    $this->actingAs($user)->get(route('pos.patrons.import.create'))->assertForbidden();
    $this->actingAs($user)->post(route('pos.patrons.import.store'), [])->assertForbidden();
})->with(['student_ag_basic', 'student_ag_extended', 'technical_admin', 'student']);

function importXlsx(array $rows): string
{
    $strings = [];
    $xml = '';

    foreach ($rows as $r => $cells) {
        $xml .= '<row r="'.($r + 1).'">';

        foreach ($cells as $c => $value) {
            $ref = chr(65 + $c).($r + 1);

            if (is_int($value)) {
                $xml .= '<c r="'.$ref.'" s="1"><v>'.$value.'</v></c>';
            } else {
                $strings[] = $value;
                $xml .= '<c r="'.$ref.'" t="s"><v>'.(count($strings) - 1).'</v></c>';
            }
        }

        $xml .= '</row>';
    }

    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Klassenliste" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="x" Target="worksheets/sheet1.xml"/></Relationships>');
    $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.implode('', array_map(static fn (string $t): string => '<si><t>'.htmlspecialchars($t).'</t></si>', $strings)).'</sst>');
    $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xml.'</sheetData></worksheet>');
    $zip->close();

    return $path;
}

it('reads Excel files with date serials and refuses an empty Excel template', function (): void {
    // 41712 = 14.03.2014 in Excel
    $rows = app(PatronCsvParser::class)->parse(importXlsx([['Vorname', 'Nachname', 'Geburtsdatum', 'E-Mail'], ['Mia', 'Müller', 41712, 'mia@example.invalid'], ['Jonas', 'Schön', '02.11.2012', '']]));

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['values'])->toBe(['vorname' => 'Mia', 'nachname' => 'Müller', 'geburtsdatum' => '2014-03-14', 'email' => 'mia@example.invalid', 'klasse' => ''])
        ->and($rows[1]['values']['geburtsdatum'])->toBe('02.11.2012');

    $template = tempnam(sys_get_temp_dir(), 'tpl');
    file_put_contents($template, (new XlsxTemplate)->build());

    expect(fn () => app(PatronCsvParser::class)->parse($template))->toThrow(InvalidArgumentException::class, 'keine Datenzeilen');
});

it('accepts an Excel upload in the import workflow', function (): void {
    Storage::fake('local');
    [, $classes] = importSchool();
    $staff = importUser();

    $upload = UploadedFile::fake()->createWithContent('klasse.xlsx', (string) file_get_contents(importXlsx([['vorname', 'nachname', 'geburtsdatum'], ['Lena', 'Excel', 41712]])));
    $response = $this->actingAs($staff)->post(route('pos.patrons.import.store'), ['file' => $upload, 'school_class_id' => (string) $classes['6b']->getKey()]);
    $response->assertRedirect();

    $this->actingAs($staff)->get($response->headers->get('Location'))->assertOk()->assertSee('Excel');
});

it('reads the class from the file when no class is chosen and flags unknown or missing classes', function (): void {
    [, $classes] = importSchool();
    $rows = importRows("vorname;nachname;geburtsdatum;klasse\nMia;Eins;14.03.2014;5a\nBen;Zwei;01.02.2013;6 B\nEva;Drei;03.03.2012;9z\nUwe;Vier;04.04.2012;\n");

    $plan = app(PatronImportPlanner::class)->plan($rows, null);

    expect($plan['rows'][0]['class_name'])->toBe('5a')->and($plan['rows'][0]['status'])->toBe('new')
        ->and($plan['rows'][1]['class_name'])->toBe('6b')
        ->and($plan['rows'][2]['status'])->toBe('error')->and($plan['rows'][2]['messages'][0])->toContain('9z')
        ->and($plan['rows'][3]['status'])->toBe('error')->and($plan['rows'][3]['messages'][0])->toContain('fehlt')
        ->and($plan['counts'])->toBe(['new' => 2, 'update' => 0, 'existing' => 0, 'duplicate' => 0, 'error' => 2]);

    // Fehlerfreie Datei mit zwei Klassen: beide Klassen werden bedient.
    $ok = importRows("vorname;nachname;geburtsdatum;klasse\nMia;Eins;14.03.2014;5a\nBen;Zwei;01.02.2013;6b\n");
    $result = app(ImportPatronsAction::class)->execute($ok, null, importUser(), false);

    expect($result)->toBe(['created' => 2, 'updated' => 0, 'skipped' => 0])
        ->and(Patron::query()->where('first_name', 'Mia')->firstOrFail()->school_class_id)->toBe((string) $classes['5a']->getKey())
        ->and(Patron::query()->where('first_name', 'Ben')->firstOrFail()->school_class_id)->toBe((string) $classes['6b']->getKey());
});

it('updates existing people only on request: new e-mail and class change, never name or birth date', function (): void {
    [, $classes] = importSchool();
    $staff = importUser();
    $mia = Patron::query()->create(['library_number' => '111111', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Mia', 'last_name' => 'Alt', 'birth_date' => '2014-03-14', 'email' => 'alt@example.invalid', 'school_class_id' => $classes['5a']->getKey()]);
    $gone = Patron::query()->create(['library_number' => '222222', 'kind' => PatronKind::Student, 'status' => PatronStatus::Departed, 'first_name' => 'Ben', 'last_name' => 'Weg', 'birth_date' => '2013-02-01', 'school_class_id' => $classes['5a']->getKey()]);
    $same = Patron::query()->create(['library_number' => '333333', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Eva', 'last_name' => 'Gleich', 'birth_date' => '2012-03-03', 'email' => 'eva@example.invalid', 'school_class_id' => $classes['6b']->getKey()]);
    $rows = importRows("vorname;nachname;geburtsdatum;email;klasse\nMia;Alt;14.03.2014;neu@example.invalid;6b\nBen;Weg;01.02.2013;ben@example.invalid;6b\nEva;Gleich;03.03.2012;;6b\n");

    // Ohne Aktualisieren bleibt alles, wie es ist.
    $plain = app(PatronImportPlanner::class)->plan($rows, null);
    expect($plain['counts'])->toBe(['new' => 0, 'update' => 0, 'existing' => 3, 'duplicate' => 0, 'error' => 0]);
    app(ImportPatronsAction::class)->execute($rows, null, $staff, false);
    expect($mia->refresh()->email)->toBe('alt@example.invalid');

    $plan = app(PatronImportPlanner::class)->plan($rows, null, true);
    expect($plan['counts'])->toBe(['new' => 0, 'update' => 1, 'existing' => 2, 'duplicate' => 0, 'error' => 0])
        ->and($plan['rows'][0]['changes'])->toBe(['E-Mail: alt@example.invalid → neu@example.invalid', 'Klasse: 5a → 6b']);

    $result = app(ImportPatronsAction::class)->execute($rows, null, $staff, true);

    expect($result)->toBe(['created' => 0, 'updated' => 1, 'skipped' => 2])
        ->and($mia->refresh()->email)->toBe('neu@example.invalid')
        ->and($mia->school_class_id)->toBe((string) $classes['6b']->getKey())
        ->and($mia->last_name)->toBe('Alt')->and($mia->library_number)->toBe('111111')
        ->and($gone->refresh()->school_class_id)->toBe((string) $classes['5a']->getKey())
        ->and($same->refresh()->email)->toBe('eva@example.invalid');
});

it('offers the update option and class column on the import page and shows the changes in the preview', function (): void {
    Storage::fake('local');
    [, $classes] = importSchool();
    $staff = importUser();
    Patron::query()->create(['library_number' => '111111', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Mia', 'last_name' => 'Alt', 'birth_date' => '2014-03-14', 'email' => 'alt@example.invalid', 'school_class_id' => $classes['5a']->getKey()]);

    $this->actingAs($staff)->get(route('pos.patrons.import.create'))->assertSee('Vorhandene Personen aktualisieren')->assertSee('Die Klasse steht in der Datei');

    $upload = UploadedFile::fake()->createWithContent('liste.csv', "vorname;nachname;geburtsdatum;email;klasse\nMia;Alt;14.03.2014;neu@example.invalid;6b\n");
    $response = $this->actingAs($staff)->post(route('pos.patrons.import.store'), ['file' => $upload, 'update' => '1']);
    $response->assertRedirect();

    $this->actingAs($staff)->get($response->headers->get('Location'))->assertOk()->assertSee('Wird aktualisiert')->assertSee('alt@example.invalid → neu@example.invalid')->assertSee('Anlegen und aktualisieren');
});
