<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\ClassificationImportDraft;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Die Begrenzung der Anfragen ist im Test nicht das Thema; sie zählt Vorschau und Import zusammen.
beforeEach(fn () => $this->withoutMiddleware(ThrottleRequests::class));

function classificationImportUser(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** phpMyAdmin-Export mit Kopf, Datenbank und Tabelle. */
function classificationJson(string $table, array $rows): string
{
    return (string) json_encode([
        ['type' => 'header', 'version' => '4.9.11', 'comment' => 'Export to JSON plugin for PHPMyAdmin'],
        ['type' => 'database', 'name' => 'dbs_test'],
        ['type' => 'table', 'name' => $table, 'database' => 'dbs_test', 'data' => $rows],
    ], JSON_UNESCAPED_UNICODE);
}

function classificationTopicFile(array $rows = []): UploadedFile
{
    return UploadedFile::fake()->createWithContent('mediaTopicList.json', classificationJson('mediaTopicList', $rows ?: classificationDefaultTopics()));
}

function classificationSignatureFile(array $rows): UploadedFile
{
    return UploadedFile::fake()->createWithContent('mediaSignatures.json', classificationJson('mediaSignatures', $rows));
}

/** @return list<array<string, string>> */
function classificationDefaultTopics(): array
{
    return [
        ['id' => '1', 'public_topic_id' => '1000', 'main_topic' => '0', 'topic' => 'Geschichten', 'description' => 'Erzählungen'],
        ['id' => '6', 'public_topic_id' => '1101', 'main_topic' => '1', 'topic' => 'Abenteuer', 'description' => 'Spannende Reisen'],
        ['id' => '7', 'public_topic_id' => '1102', 'main_topic' => '1', 'topic' => 'Freundschaft & Schule', 'description' => ''],
        ['id' => '42', 'public_topic_id' => '6000', 'main_topic' => '0', 'topic' => 'Jugendliteratur', 'description' => 'Ab Klasse 5'],
        ['id' => '43', 'public_topic_id' => '6101', 'main_topic' => '42', 'topic' => 'Coming-of-Age', 'description' => null],
    ];
}

/** Alle 290 Regalbretter der Bibliothek, wie in den Altdaten (Themen nur bei den ersten). */
function classificationFullSignatures(): array
{
    $layout = [
        ['I', 'A', [1, 2, 3], 7], ['I', 'A', [4, 5, 6], 4], ['I', 'B', range(1, 6), 7], ['I', 'C', [1, 2, 3], 4],
        ['II', 'A', range(1, 8), 7], ['II', 'B', range(1, 8), 7], ['II', 'C', range(1, 7), 7], ['II', 'D', [1, 2], 7],
        ['II', 'E', [1, 2], 7], ['II', 'F', [1], 7], ['III', 'A', [1], 7],
    ];
    $rows = [];
    $id = 0;

    foreach ($layout as [$group, $area, $racks, $boards]) {
        foreach ($racks as $rack) {
            foreach (array_slice(range('a', 'g'), 0, $boards) as $board) {
                $id++;
                $rows[] = ['id' => (string) $id, 'signature' => "{$group}. {$area} {$rack} {$board}", 'topic_ids' => $id === 1 ? '[6]' : ($id === 2 ? '[6, 7]' : '[]')];
            }
        }
    }

    return $rows;
}

function classificationPreview(object $test, User $user, array $files): string
{
    $response = $test->actingAs($user)->post(route('administration.classification-import.preview'), $files);
    $response->assertRedirect();

    return basename((string) parse_url($response->headers->get('Location'), PHP_URL_PATH));
}

function classificationFingerprint(object $test, User $user, string $draftId): string
{
    $html = $test->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draftId]))->assertOk()->getContent();
    preg_match('/name="fingerprint" value="([a-f0-9]{64})"/', $html, $m);

    return $m[1] ?? '';
}

function classificationConfirm(object $test, User $user, string $draftId, array $extra = [])
{
    return $test->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $draftId]), [
        'fingerprint' => classificationFingerprint($test, $user, $draftId),
        'confirm' => '1',
        ...$extra,
    ]);
}

it('imports topics and all 290 shelves with the right hierarchy', function (): void {
    $user = classificationImportUser();
    $draft = classificationPreview($this, $user, ['topics' => classificationTopicFile(), 'signatures' => classificationSignatureFile(classificationFullSignatures())]);

    // Vorschau schreibt nichts.
    expect(CatalogTopic::query()->count())->toBe(0)->and(CatalogShelf::query()->count())->toBe(0);
    $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draft]))->assertOk()->assertSee('Vorschau')->assertSee('Neue Regalbretter');

    classificationConfirm($this, $user, $draft)->assertRedirect(route('administration.classification-import.index'))->assertSessionHas('import_result');

    expect(CatalogTopic::query()->count())->toBe(5)
        ->and(CatalogShelf::query()->count())->toBe(290)
        ->and(CatalogSignature::query()->count())->toBe(290);

    $child = CatalogTopic::query()->where('legacy_id', '43')->firstOrFail();
    expect($child->parent->name)->toBe('Jugendliteratur')->and($child->public_key)->toBe('6101')->and($child->legacy_source)->toBe('vdbs-legacy');

    $first = CatalogShelf::query()->where('code', 'I. A 1 a')->firstOrFail();
    expect($first->topics->pluck('name')->all())->toBe(['Abenteuer'])->and($first->label)->toBe('Abenteuer')->and($first->board)->toBe('a');

    foreach (['II. F 1 g' => ['F', 'II', 'Lexika & Nachschlagewerke', 'Fachliteratur'], 'III. A 1 g' => ['A', 'III', 'Kinder', 'Antidiskriminierungs- & Sensibilisierungsliteratur']] as $code => [$area, $group, $areaName, $groupName]) {
        $shelf = CatalogShelf::query()->where('code', $code)->firstOrFail();
        $rack = $shelf->rack;
        expect($rack)->toBeInstanceOf(CatalogShelfSection::class)
            ->and($rack->code)->toBe('1')
            ->and($rack->parent->code)->toBe($area)->and($rack->parent->name)->toBe($areaName)
            ->and($rack->parent->parent->code)->toBe($group)->and($rack->parent->parent->name)->toBe($groupName)
            ->and($shelf->board)->toBe('g');
    }

    // Regale je Bereich: I.A 6, I.B 6, I.C 3, II.A 8, II.B 8, II.C 7, II.D 2, II.E 2, II.F 1, III.A 1 = 44 Regale; 3 Gruppen, 10 Bereiche (III.B hat keine Regale).
    expect(CatalogShelfSection::query()->where('kind', 'rack')->count())->toBe(44)
        ->and(CatalogShelfSection::query()->where('kind', 'area')->count())->toBe(10)
        ->and(CatalogShelfSection::query()->where('kind', 'group')->count())->toBe(3)
        ->and(AuditEvent::query()->where('action', 'catalog.classification.imported')->count())->toBe(1);
});

it('imports only the topics', function (): void {
    $user = classificationImportUser();
    $draft = classificationPreview($this, $user, ['topics' => classificationTopicFile()]);
    classificationConfirm($this, $user, $draft)->assertSessionHas('import_result');

    expect(CatalogTopic::query()->count())->toBe(5)->and(CatalogShelf::query()->count())->toBe(0)->and(CatalogSignature::query()->count())->toBe(0);
});

it('imports shelves for topics that already exist', function (): void {
    $user = classificationImportUser();
    classificationConfirm($this, $user, classificationPreview($this, $user, ['topics' => classificationTopicFile()]));

    $draft = classificationPreview($this, $user, ['signatures' => classificationSignatureFile([['id' => '1', 'signature' => 'I. A 1 a', 'topic_ids' => '[6, 43]']])]);
    classificationConfirm($this, $user, $draft)->assertSessionHas('import_result');

    $shelf = CatalogShelf::query()->where('code', 'I. A 1 a')->firstOrFail();
    expect($shelf->topics->pluck('legacy_id')->all())->toBe(['6', '43']);
});

it('is idempotent: importing the same files again creates nothing', function (): void {
    $user = classificationImportUser();
    $files = static fn (): array => ['topics' => classificationTopicFile(), 'signatures' => classificationSignatureFile(classificationFullSignatures())];
    classificationConfirm($this, $user, classificationPreview($this, $user, $files()));

    $counts = [CatalogTopic::query()->count(), CatalogShelf::query()->count(), CatalogSignature::query()->count(), DB::table('catalog_shelf_topics')->count(), CatalogShelfSection::query()->count()];

    $second = classificationPreview($this, $user, $files());
    $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $second]))->assertOk()->assertSee('nichts zu übernehmen');
    classificationConfirm($this, $user, $second);

    expect([CatalogTopic::query()->count(), CatalogShelf::query()->count(), CatalogSignature::query()->count(), DB::table('catalog_shelf_topics')->count(), CatalogShelfSection::query()->count()])->toBe($counts);
});

it('blocks duplicate ids, duplicate public keys, missing parents and cycles', function (): void {
    $user = classificationImportUser();

    $rows = [
        ['id' => '1', 'public_topic_id' => '1000', 'main_topic' => '0', 'topic' => 'A', 'description' => ''],
        ['id' => '1', 'public_topic_id' => '1001', 'main_topic' => '0', 'topic' => 'A doppelt', 'description' => ''],
        ['id' => '2', 'public_topic_id' => '1000', 'main_topic' => '0', 'topic' => 'B gleicher Schlüssel', 'description' => ''],
        ['id' => '3', 'public_topic_id' => '1003', 'main_topic' => '99', 'topic' => 'C ohne Eltern', 'description' => ''],
        ['id' => '4', 'public_topic_id' => '1004', 'main_topic' => '5', 'topic' => 'D', 'description' => ''],
        ['id' => '5', 'public_topic_id' => '1005', 'main_topic' => '4', 'topic' => 'E', 'description' => ''],
        ['id' => '8', 'public_topic_id' => '1008', 'main_topic' => '8', 'topic' => 'F sich selbst', 'description' => ''],
        ['id' => '9', 'public_topic_id' => '1009', 'main_topic' => '0', 'topic' => '', 'description' => ''],
    ];

    $draft = classificationPreview($this, $user, ['topics' => classificationTopicFile($rows)]);
    $html = $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draft]))->assertOk()->getContent();

    expect($html)->toContain('kommt doppelt vor')->toContain('öffentliche Schlüssel 1000')->toContain('Elternthema 99')->toContain('Zyklus')->toContain('eigenes Elternthema')->toContain('Es fehlt der Name');
    expect($html)->not->toContain('name="fingerprint"');

    $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $draft]), ['fingerprint' => str_repeat('a', 64), 'confirm' => '1'])
        ->assertRedirect()->assertSessionHas('import_error');
    expect(CatalogTopic::query()->count())->toBe(0);
});

it('reports unknown topic references and invalid signatures', function (): void {
    $user = classificationImportUser();
    classificationConfirm($this, $user, classificationPreview($this, $user, ['topics' => classificationTopicFile()]));

    $rows = [
        ['id' => '1', 'signature' => 'I. A 1 a', 'topic_ids' => '[6, 999]'],
        ['id' => '2', 'signature' => 'Regal Eins', 'topic_ids' => '[]'],
        ['id' => '3', 'signature' => 'I. A 1 c', 'topic_ids' => 'kein json'],
        ['id' => '4', 'signature' => 'I. A 1 d', 'topic_ids' => '[]'],
        ['id' => '5', 'signature' => 'I.  A 1 d', 'topic_ids' => '[]'],
    ];

    $draft = classificationPreview($this, $user, ['signatures' => classificationSignatureFile($rows)]);
    $html = $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draft]))->assertOk()->getContent();

    expect($html)->toContain('Thema 999')->toContain('nicht die Form')->toContain('keine gültige Liste')->toContain('kommt doppelt vor');
    expect(CatalogShelf::query()->count())->toBe(0);
});

it('shows conflicts with existing topics and only applies them when asked', function (): void {
    $user = classificationImportUser();
    classificationConfirm($this, $user, classificationPreview($this, $user, ['topics' => classificationTopicFile()]));

    $changed = classificationDefaultTopics();
    $changed[1]['topic'] = 'Abenteuer & Entdeckungen';
    $changed[1]['description'] = 'Neue Beschreibung';

    $draft = classificationPreview($this, $user, ['topics' => classificationTopicFile($changed)]);
    $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draft]))->assertOk()->assertSee('weicht vom Bestand ab')->assertSee('Abenteuer &amp; Entdeckungen', false);

    // Ohne Haken bleibt das Thema unverändert.
    classificationConfirm($this, $user, $draft)->assertSessionHas('import_result', static fn (array $r): bool => $r['skipped_conflicts'] === 1 && $r['updated_topics'] === 0);
    expect(CatalogTopic::query()->where('legacy_id', '6')->value('name'))->toBe('Abenteuer');

    // Mit ausdrücklicher Zustimmung wird übernommen.
    $again = classificationPreview($this, $user, ['topics' => classificationTopicFile($changed)]);
    $response = classificationConfirm($this, $user, $again, ['update_existing' => '1']);
    $response->assertSessionHas('import_result', static fn (array $r): bool => $r['updated_topics'] === 1);
    expect(CatalogTopic::query()->where('legacy_id', '6')->value('name'))->toBe('Abenteuer & Entdeckungen');
});

it('rejects a public key that belongs to another existing topic', function (): void {
    $user = classificationImportUser();
    CatalogTopic::query()->create(['name' => 'Handgepflegt', 'public_key' => '1101']);

    $draft = classificationPreview($this, $user, ['topics' => classificationTopicFile()]);
    $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draft]))->assertOk()->assertSee('schon beim Thema')->assertSee('Handgepflegt')->assertDontSee('name="fingerprint"', false);
});

it('rolls everything back when writing fails halfway', function (): void {
    $user = classificationImportUser();
    $draft = classificationPreview($this, $user, ['topics' => classificationTopicFile(), 'signatures' => classificationSignatureFile(classificationFullSignatures())]);

    // Ein Regalbrett-Datensatz scheitert mitten im Import: der Code kollidiert hart (Unique-Index) durch einen Auslöser in der Datenbank.
    CatalogShelf::saving(static function (CatalogShelf $shelf): void {
        if ($shelf->code === 'II. A 1 a') {
            throw new RuntimeException('Simulierter Fehler');
        }
    });

    $print = classificationFingerprint($this, $user, $draft);
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $draft]), ['fingerprint' => $print, 'confirm' => '1']))->toThrow(RuntimeException::class);

    CatalogShelf::flushEventListeners();

    expect(CatalogTopic::query()->count())->toBe(0)->and(CatalogShelf::query()->count())->toBe(0)->and(CatalogSignature::query()->count())->toBe(0)
        ->and(ClassificationImportDraft::query()->whereNotNull('consumed_at')->count())->toBe(0);
});

it('keeps the import away from guests and users without the shelf permission', function (): void {
    $this->get(route('administration.classification-import.index'))->assertRedirect(route('login'));
    $this->post(route('administration.classification-import.preview'))->assertRedirect(route('login'));

    foreach (['student', 'teacher', 'student_ag_basic'] as $role) {
        $user = classificationImportUser($role);
        $this->actingAs($user)->get(route('administration.classification-import.index'))->assertForbidden();
        $this->actingAs($user)->post(route('administration.classification-import.preview'), ['topics' => classificationTopicFile()])->assertForbidden();
    }

    $this->actingAs(classificationImportUser('staff'))->get(route('administration.classification-import.index'))->assertOk()->assertSee('Themen & Regalbretter importieren');
});

it('refuses a tampered confirmation: wrong fingerprint, other user, changed file, expired or reused draft', function (): void {
    $user = classificationImportUser();
    $draftId = classificationPreview($this, $user, ['topics' => classificationTopicFile()]);

    // Falsche Prüfsumme.
    $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $draftId]), ['fingerprint' => str_repeat('0', 64), 'confirm' => '1'])->assertSessionHas('import_error');
    // Ohne Bestätigung.
    $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $draftId]), ['fingerprint' => classificationFingerprint($this, $user, $draftId)])->assertSessionHasErrors('confirm');
    // Anderer Benutzer sieht den Entwurf nicht.
    $other = classificationImportUser();
    $this->actingAs($other)->get(route('administration.classification-import.show', ['draftId' => $draftId]))->assertNotFound();
    $this->actingAs($other)->post(route('administration.classification-import.apply', ['draftId' => $draftId]), ['fingerprint' => 'x', 'confirm' => '1'])->assertNotFound();

    // Datei auf der Platte verändert.
    $draft = ClassificationImportDraft::query()->findOrFail($draftId);
    Storage::disk('local')->put((string) $draft->topics_path, classificationJson('mediaTopicList', [['id' => '77', 'public_topic_id' => '7700', 'main_topic' => '0', 'topic' => 'Eingeschmuggelt', 'description' => '']]));
    $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $draftId]), ['fingerprint' => str_repeat('0', 64), 'confirm' => '1'])->assertRedirect(route('administration.classification-import.index'))->assertSessionHas('import_error');
    expect(CatalogTopic::query()->count())->toBe(0);

    // Abgelaufen.
    $fresh = classificationPreview($this, $user, ['topics' => classificationTopicFile()]);
    $print = classificationFingerprint($this, $user, $fresh);
    ClassificationImportDraft::query()->whereKey($fresh)->update(['expires_at' => now()->subMinute()]);
    $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $fresh]), ['fingerprint' => $print, 'confirm' => '1'])->assertRedirect(route('administration.classification-import.index'));
    expect(CatalogTopic::query()->count())->toBe(0);

    // Zweimal bestätigen: der zweite Versuch greift nicht mehr.
    $once = classificationPreview($this, $user, ['topics' => classificationTopicFile()]);
    $print = classificationFingerprint($this, $user, $once);
    $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $once]), ['fingerprint' => $print, 'confirm' => '1'])->assertSessionHas('import_result');
    $this->actingAs($user)->post(route('administration.classification-import.apply', ['draftId' => $once]), ['fingerprint' => $print, 'confirm' => '1'])->assertSessionHas('import_error');
    expect(CatalogTopic::query()->count())->toBe(5);
});

it('rejects uploads that are not json files of acceptable size', function (): void {
    $user = classificationImportUser();

    $this->actingAs($user)->post(route('administration.classification-import.preview'), [])->assertSessionHasErrors('topics');
    $this->actingAs($user)->post(route('administration.classification-import.preview'), ['topics' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;')])->assertSessionHasErrors('topics');
    $this->actingAs($user)->post(route('administration.classification-import.preview'), ['topics' => UploadedFile::fake()->create('gross.json', 3000, 'application/json')])->assertSessionHasErrors('topics');

    $broken = UploadedFile::fake()->createWithContent('kaputt.json', '{ das ist kein json');
    $draft = classificationPreview($this, $user, ['topics' => $broken]);
    $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draft]))->assertOk()->assertSee('kein gültiges JSON')->assertDontSee(storage_path());
});

it('does not touch copies, titles or existing shelves and their settings', function (): void {
    $user = classificationImportUser();

    $title = Title::query()->create(['preferred_title' => 'Ein Buch', 'sort_title' => 'Ein Buch']);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    $copy = Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => '4711', 'status' => 'active', 'shelf_location' => 'IA1a']);

    // Ein Regalbrett mit abweichender Schreibweise, eigener Reihenfolge und ausgeschaltet.
    $shelf = CatalogShelf::query()->create(['code' => 'IA1a', 'label' => 'Meine Beschriftung', 'sort_order' => 999, 'is_active' => false]);

    $draft = classificationPreview($this, $user, ['topics' => classificationTopicFile(), 'signatures' => classificationSignatureFile([['id' => '1', 'signature' => 'I. A 1 a', 'topic_ids' => '[6]']])]);
    $html = $this->actingAs($user)->get(route('administration.classification-import.show', ['draftId' => $draft]))->assertOk()->getContent();
    expect($html)->toContain('wird als „I. A 1 a“ erkannt');

    classificationConfirm($this, $user, $draft)->assertSessionHas('import_result');

    expect($copy->refresh()->shelf_location)->toBe('IA1a')
        ->and(Copy::query()->count())->toBe(1)->and(Title::query()->count())->toBe(1)
        ->and($shelf->refresh()->code)->toBe('IA1a')->and($shelf->label)->toBe('Meine Beschriftung')->and($shelf->sort_order)->toBe(999)->and($shelf->is_active)->toBeFalse()
        ->and(CatalogShelf::query()->count())->toBe(1)
        ->and($shelf->topics()->count())->toBe(1);
});
