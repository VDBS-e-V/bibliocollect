<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Queries\SafeMetadataProposalsQuery;
use App\Modules\Catalog\Services\MetadataProposalService;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\DnbRecordXml;

uses(RefreshDatabase::class);

function bulkUser(string $role = 'staff'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

/** Ausgabe mit verlorenem Umlaut und einer Verantwortlichen; optional ohne Jahr. */
function bulkEdition(string $title, string $dnbId, bool $withContributor = true): Edition
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create([
        'title_id' => $titleModel->getKey(),
        'isbn' => '9783000000003',
        'publisher_name' => 'Verlag',
        'media_type' => 'book',
        'source_record_id' => $dnbId,
    ]);

    if ($withContributor) {
        $contributor = Contributor::query()->create(['display_name' => 'Autor Beispiel', 'sort_name' => 'Beispiel, Autor']);
        TitleContribution::query()->create(['title_id' => $titleModel->getKey(), 'contributor_id' => $contributor->getKey(), 'role_key' => 'author']);
    }

    return $edition;
}

it('treats a fully proven fix as safe and everything else as manual work', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'id' => '1000000001',
        'isbns' => ['9783000000003'],
        'title' => 'Räuber Hotzenplotz',
        'year' => '2012',
    ]))]);

    bulkEdition('Ra?uber Hotzenplotz', '1000000001');
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $review = CatalogMetadataReview::query()->firstOrFail();
    app(MetadataProposalService::class)->propose($review);

    $safe = app(SafeMetadataProposalsQuery::class)->execute();

    expect($safe)->toHaveCount(1)
        ->and($safe[0]['keys'])->toContain('title.preferred_title');
});

it('applies safe proposals in bulk only after confirmation and keeps manual cases open', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'id' => '1000000001',
        'isbns' => ['9783000000003'],
        'title' => 'Räuber Hotzenplotz',
        'year' => '2012',
    ]))]);

    $edition = bulkEdition('Ra?uber Hotzenplotz', '1000000001');
    app(ScanCatalogMetadataQualityAction::class)->execute();
    app(MetadataProposalService::class)->propose(CatalogMetadataReview::query()->firstOrFail());

    $staff = bulkUser();

    $this->actingAs($staff)->get(route('pos.catalog.quality.safe'))
        ->assertOk()
        ->assertSee('Räuber Hotzenplotz')
        ->assertSee('Alle eindeutigen Vorschläge übernehmen');

    $this->actingAs($staff)->post(route('pos.catalog.quality.safe.apply'), [])->assertSessionHasErrors('confirm');
    expect($edition->title->fresh()->preferred_title)->toBe('Ra?uber Hotzenplotz');

    $this->actingAs($staff)->post(route('pos.catalog.quality.safe.apply'), ['confirm' => '1'])
        ->assertRedirect(route('pos.catalog.quality.index'))
        ->assertSessionHas('catalog_success');

    expect($edition->title->fresh()->preferred_title)->toBe('Räuber Hotzenplotz')
        ->and($edition->fresh()->publication_year)->toBe(2012)
        ->and(CatalogMetadataReview::query()->firstOrFail()->issues)->not->toContain('lost_characters', 'missing_year');
});

it('keeps cases with warnings or person changes out of the bulk list', function (): void {
    // Der Titel der Quelle passt nicht zur Ausgabe: Warnung, nichts vorausgewählt.
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'id' => '1000000002',
        'isbns' => ['9783000000003'],
        'title' => 'Ein ganz anderes Buch',
        'year' => '1999',
    ]))]);

    bulkEdition('Ra?uber Hotzenplotz', '1000000002');
    app(ScanCatalogMetadataQualityAction::class)->execute();
    app(MetadataProposalService::class)->propose(CatalogMetadataReview::query()->firstOrFail());

    expect(app(SafeMetadataProposalsQuery::class)->execute())->toBe([]);
});

it('fetches proposals in advance without touching the catalog', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(DnbRecordXml::record([
        'id' => '1000000001',
        'isbns' => ['9783000000003'],
        'title' => 'Räuber Hotzenplotz',
        'year' => '2012',
    ]))]);

    $edition = bulkEdition('Ra?uber Hotzenplotz', '1000000001');
    app(ScanCatalogMetadataQualityAction::class)->execute();

    $this->artisan('catalog:quality:propose', ['--delay' => 0])->expectsOutputToContain('1 Fälle bearbeitet: 1 mit Vorschlag');

    expect(CatalogMetadataReview::query()->firstOrFail()->proposal_state)->toBe('ready')
        ->and($edition->title->fresh()->preferred_title)->toBe('Ra?uber Hotzenplotz');

    // Ein zweiter Lauf holt nichts erneut.
    $this->artisan('catalog:quality:propose', ['--delay' => 0])->expectsOutputToContain('0 Fälle bearbeitet');
});

it('keeps the bulk page away from users without catalog rights', function (string $role): void {
    $this->actingAs(bulkUser($role))->get(route('pos.catalog.quality.safe'))->assertForbidden();
    $this->actingAs(bulkUser($role))->post(route('pos.catalog.quality.safe.apply'), ['confirm' => '1'])->assertForbidden();
})->with(['technical_admin', 'student_ag_basic', 'student']);
