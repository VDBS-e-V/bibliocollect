<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogShelfSuggester;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function suggestHelper(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, 'student_ag_basic');

    return $user;
}

function suggestCopy(string $barcode, string $title, array $edition = []): Copy
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $editionModel = Edition::query()->create(array_merge(['title_id' => $titleModel->getKey(), 'media_type' => 'book'], $edition));

    return Copy::query()->create(['edition_id' => $editionModel->getKey(), 'barcode' => $barcode, 'status' => 'active']);
}

function suggestShelves(): void
{
    $space = CatalogTopic::query()->create(['name' => 'Weltraum', 'description' => 'Sterne, Planeten und Raumfahrt']);
    $animals = CatalogTopic::query()->create(['name' => 'Tiere & Natur']);
    CatalogShelf::query()->create(['code' => 'I. A 3 b', 'label' => 'Weltraum'])->topics()->attach($space->getKey(), ['position' => 1]);
    CatalogShelf::query()->create(['code' => 'I. A 2 d', 'label' => 'Tiere & Natur'])->topics()->attach($animals->getKey(), ['position' => 1]);
    CatalogShelf::query()->create(['code' => 'I. A 4 c', 'label' => 'Grusel (ab 9/10)']);
    CatalogShelf::query()->create(['code' => 'I. A 1 c', 'label' => 'Lesestart (Kl. 1–2)']);
}

it('ranks the shelves by keywords, title and other details of the book', function (): void {
    suggestShelves();
    $copy = suggestCopy('0040001', 'Reise zu den Sternen', ['subject_keywords' => 'Weltraum; Planeten; Raumfahrt', 'summary' => 'Eine Reise durch das Sonnensystem.']);
    $none = suggestCopy('0040002', 'Etwas ganz anderes', ['subject_keywords' => 'Kochen']);

    $suggestions = app(CatalogShelfSuggester::class)->suggest($copy->edition);

    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0]['code'])->toBe('I. A 3 b')
        ->and($suggestions[0]['reasons'])->toContain('weltraum')
        ->and(app(CatalogShelfSuggester::class)->suggest($none->edition))->toBe([]);
});

it('uses the age of the book to prefer the matching shelf', function (): void {
    suggestShelves();
    $young = suggestCopy('0040010', 'Lesen lernen', ['subject_keywords' => 'Lesestart; Erstleser', 'minimum_age' => 6]);
    $old = suggestCopy('0040011', 'Lesen lernen', ['subject_keywords' => 'Lesestart; Erstleser', 'minimum_age' => 14]);

    $forYoung = app(CatalogShelfSuggester::class)->suggest($young->edition);
    $forOld = app(CatalogShelfSuggester::class)->suggest($old->edition);

    expect($forYoung[0]['code'])->toBe('I. A 1 c')->and($forYoung[0]['score'])->toBeGreaterThan($forOld[0]['score'] ?? 0);
});

it('shows and preselects the suggestion by keywords when the book has no topic', function (): void {
    suggestShelves();
    suggestCopy('0040020', 'Wunder des Alls', ['subject_keywords' => 'Weltraum, Planeten']);

    $this->actingAs(suggestHelper())->get(route('pos.shelving', ['buch' => '0040020']))->assertOk()
        ->assertSee('Nach Schlagwörtern und Angaben zum Buch')
        ->assertSee('<option value="I. A 3 b" selected>', false)
        ->assertSee('weltraum');

    // Hat das Medium ein Thema mit Regalbrett, bleibt dieses Vorrang.
    $topic = CatalogTopic::query()->where('name', 'Tiere & Natur')->firstOrFail();
    suggestCopy('0040021', 'Tiere im Wald', ['subject_keywords' => 'Weltraum', 'local_classification' => $topic->name]);
    $this->actingAs(suggestHelper())->get(route('pos.shelving', ['buch' => '0040021']))->assertSee('<option value="I. A 2 d" selected>', false);
});

it('goes through the stack topic by topic and carries on with the next book of the topic', function (): void {
    suggestShelves();
    suggestCopy('0040030', 'Erstes', ['local_classification' => 'Weltraum']);
    suggestCopy('0040031', 'Zweites', ['local_classification' => 'Weltraum']);
    suggestCopy('0040032', 'Drittes', ['local_classification' => 'Tiere & Natur']);
    suggestCopy('0040033', 'Ohne Thema');
    $helper = suggestHelper();

    $page = $this->actingAs($helper)->get(route('pos.shelving'))->assertOk();
    $page->assertSee('Nach Thema einsortieren')->assertSee('Weltraum')->assertSee('Ohne Thema');

    // Thema wählen: das erste Buch des Themas ist dran, das Formular merkt sich das Thema.
    $this->actingAs($helper)->get(route('pos.shelving', ['thema' => 'Weltraum']))->assertOk()->assertSee('Erstes')->assertSee('name="thema" value="Weltraum"', false);

    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0040030', 'regalbrett' => 'I. A 3 b', 'thema' => 'Weltraum'])->assertRedirect(route('pos.shelving', ['thema' => 'Weltraum']));
    $this->actingAs($helper)->get(route('pos.shelving', ['thema' => 'Weltraum']))->assertSee('name="buch" value="0040031"', false);

    $this->actingAs($helper)->post(route('pos.shelving.scan'), ['buch' => '0040031', 'regalbrett' => 'I. A 3 b', 'thema' => 'Weltraum']);
    $this->actingAs($helper)->get(route('pos.shelving', ['thema' => 'Weltraum']))->assertSee('Alle Bücher zum Thema „Weltraum“ sind einsortiert.');

    $this->actingAs($helper)->get(route('pos.shelving', ['thema' => '__ohne__']))->assertSee('Ohne Thema');
});

it('reads the stored age recommendation (JSON) without error and uses it for the age match', function (): void {
    suggestShelves();
    $unknown = suggestCopy('0040010', 'Reise zu den Sternen', ['subject_keywords' => 'Weltraum', 'age_recommendation' => ['raw' => 'Keine Angabe']]);
    $aged = suggestCopy('0040011', 'Gruseliges', ['subject_keywords' => 'Grusel', 'age_recommendation' => ['raw' => 'ab 9 Jahren']]);

    expect(app(CatalogShelfSuggester::class)->suggest($unknown->edition))->not->toBeEmpty();

    $codes = array_column(app(CatalogShelfSuggester::class)->suggest($aged->edition), 'code');
    expect($codes)->toContain('I. A 4 c');

    $helper = suggestHelper();
    $this->actingAs($helper)->get(route('pos.shelving', ['buch' => '0040010']))->assertOk();
});
