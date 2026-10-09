<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Queries\SafeMetadataProposalsQuery;
use App\Modules\Catalog\Services\QualityQueueSettings;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\DnbRecordXml;

uses(RefreshDatabase::class);

function queueUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** Ausgabe mit einer Verantwortlichen; auf Wunsch ohne Jahr oder mit verlorenem Umlaut im Titel. */
function queueEdition(string $title, string $dnbId, ?string $year = '2010', string $isbn = '9783000000003', ?string $summary = null, ?string $keywords = 'Abenteuer'): Edition
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create([
        'title_id' => $titleModel->getKey(), 'isbn' => $isbn, 'publisher_name' => 'Verlag', 'media_type' => 'book',
        'source_record_id' => $dnbId, 'publication_year' => $year, 'summary' => $summary, 'subject_keywords' => $keywords,
    ]);
    $contributor = Contributor::query()->firstOrCreate(['display_name' => 'Autor Beispiel'], ['sort_name' => 'Beispiel, Autor']);
    TitleContribution::query()->create(['title_id' => $titleModel->getKey(), 'contributor_id' => $contributor->getKey(), 'role_key' => 'author']);

    return $edition;
}

function queueDnb(string $title = 'Gutes Buch'): void
{
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record(['id' => '1000000001', 'isbns' => ['9783000000003'], 'title' => $title, 'year' => '2012']))]);
}

it('works through the chosen problem kinds first, within the nightly limit', function (): void {
    queueDnb('Räuber');
    $noYearA = queueEdition('Ohne Jahr A', '1000000001', null);
    $lost = queueEdition('Ra?uber', '1000000002');
    $noYearB = queueEdition('Ohne Jahr B', '1000000003', null);
    app(ScanCatalogMetadataQualityAction::class)->execute();

    // Ohne Auswahl käme der Umlautfehler (höherer Schweregrad) zuerst dran.
    app(QualityQueueSettings::class)->save(['missing_year'], false, 2, null);
    $this->artisan('catalog:quality:propose', ['--delay' => 0])->expectsOutputToContain('2 Fälle bearbeitet');

    $state = static fn (Edition $edition): ?string => CatalogMetadataReview::query()->where('edition_id', $edition->getKey())->value('proposal_state');
    expect($state($noYearA))->not->toBeNull()->and($state($noYearB))->not->toBeNull()->and($state($lost))->toBeNull();

    // Der Rest folgt in der nächsten Nacht.
    $this->artisan('catalog:quality:propose', ['--delay' => 0, '--limit' => 5])->expectsOutputToContain('1 Fälle bearbeitet');
    expect($state($lost))->not->toBeNull();
});

it('leaves enrichment-only cases alone unless they are switched on', function (): void {
    queueDnb();
    $good = queueEdition('Vollständig', '1000000001', '2012', '9783000000003', null, null);
    app(ScanCatalogMetadataQualityAction::class)->execute();
    $review = CatalogMetadataReview::query()->where('edition_id', $good->getKey())->firstOrFail();
    expect($review->severity)->toBe(0);

    $this->artisan('catalog:quality:propose', ['--delay' => 0])->expectsOutputToContain('0 Fälle bearbeitet');
    expect($review->refresh()->proposal_state)->toBeNull();

    $this->artisan('catalog:quality:propose', ['--delay' => 0, '--enrichment' => true])->expectsOutputToContain('1 Fälle bearbeitet');
    expect($review->refresh()->proposal_state)->not->toBeNull();
});

it('proposes a summary from a book source when the DNB has none and keeps it out of the bulk takeover', function (): void {
    config(['catalog.quality.summary_enrichment' => true]);
    queueDnb('Gutes Buch');
    Http::fake([
        'services.dnb.de/*' => Http::response(DnbRecordXml::record(['id' => '1000000001', 'isbns' => ['9783000000003'], 'title' => 'Gutes Buch', 'year' => '2012'])),
        'www.googleapis.com/*' => Http::response(['items' => [['volumeInfo' => ['description' => 'Zwei Kinder entdecken ein Geheimnis im Wald.']]]]),
    ]);

    $edition = queueEdition('Gutes Buch', '1000000001', null);
    app(ScanCatalogMetadataQualityAction::class)->execute();
    $this->artisan('catalog:quality:propose', ['--delay' => 0])->assertSuccessful();

    $review = CatalogMetadataReview::query()->where('edition_id', $edition->getKey())->firstOrFail();
    $proposal = MetadataProposal::fromArray($review->proposal ?? []);
    $keys = array_map(static fn ($change): string => $change->key, $proposal->changes);

    expect($keys)->toContain('edition.summary')->and(implode(' ', $proposal->warnings))->toContain('Google Books')
        ->and(app(SafeMetadataProposalsQuery::class)->execute())->toBe([])
        ->and($edition->refresh()->summary)->toBeNull();
});

it('shows the data quality section and saves the choice for administration only', function (): void {
    $admin = queueUser('management');
    queueEdition('Mangel', '1000000001', null);
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $this->actingAs($admin)->get(route('administration.system.index'))->assertOk()->assertSee('Datenqualität')->assertSee('Kein Erscheinungsjahr')->assertSee('Jetzt ein Stück abarbeiten');

    $this->actingAs($admin)->post(route('administration.system.quality'), ['issues' => ['missing_year', 'mojibake'], 'enrichment' => '1', 'per_night' => 25])
        ->assertRedirect(route('administration.system.index'))->assertSessionHas('system_success');
    expect(app(QualityQueueSettings::class)->get())->toBe(['issues' => ['mojibake', 'missing_year'], 'enrichment' => true, 'per_night' => 25]);

    $this->actingAs($admin)->post(route('administration.system.quality'), ['per_night' => 0])->assertSessionHasErrors('per_night');
    $this->actingAs($admin)->post(route('administration.system.quality'), ['issues' => ['quatsch'], 'per_night' => 10])->assertSessionHasErrors('issues.0');
    $this->actingAs(queueUser('staff'))->post(route('administration.system.quality'), ['per_night' => 10])->assertForbidden();
});

it('warns when the backup was never downloaded or not for two weeks', function (): void {
    $admin = queueUser('management');

    $this->actingAs($admin)->get(route('administration.system.index'))->assertSee('Sicherung außerhalb des Servers')->assertSee('Noch nie heruntergeladen');

    app(AuditRecorder::class)->record('system.backup.downloaded', 'Datenbanksicherung heruntergeladen.');
    $this->actingAs($admin)->get(route('administration.system.index'))->assertSee('zuletzt vor 0 Tag(en) heruntergeladen')->assertDontSee('Noch nie heruntergeladen');

    $this->travel(20)->days();
    $this->actingAs($admin)->get(route('administration.system.index'))->assertSee('Zuletzt vor 20 Tagen heruntergeladen');
});
