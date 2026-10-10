<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function searchBook(string $title, array $edition = [], ?string $author = null): Title
{
    $model = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $editionModel = Edition::query()->create(['title_id' => $model->getKey(), 'media_type' => 'book', ...$edition]);
    Copy::query()->create(['edition_id' => $editionModel->getKey(), 'barcode' => md5($title), 'status' => 'active']);

    if ($author !== null) {
        $contributor = Contributor::query()->create(['display_name' => $author]);
        TitleContribution::query()->create(['title_id' => $model->getKey(), 'contributor_id' => $contributor->getKey(), 'role_key' => 'author']);
    }

    return $model;
}

it('sorts hits by relevance: title before author before keywords, ahead of the alphabet', function (): void {
    searchBook('Abenteuer im Wald', ['subject_keywords' => 'Drache, Wald']);
    searchBook('Beim Schreiben', [], 'Anna Drache');
    searchBook('Zähme deinen Drachen');
    searchBook('Drache Funkel');

    $this->get(route('public.catalog.index', ['q' => 'drache']))->assertOk()->assertSee('Beste Treffer zuerst')
        ->assertSeeInOrder(['Drache Funkel', 'Zähme deinen Drachen', 'Beim Schreiben', 'Abenteuer im Wald']);

    // Wer ausdrücklich A–Z wählt, bekommt A–Z.
    $alphabetical = $this->get(route('public.catalog.index', ['q' => 'drache', 'sort' => 'title']))->getContent();
    expect(strpos($alphabetical, 'Abenteuer im Wald'))->toBeLessThan(strpos($alphabetical, 'Zähme deinen Drachen'));
});

it('offers a spelling suggestion when nothing is found and the corrected search has hits', function (): void {
    searchBook('Harry Potter und der Stein der Weisen', [], 'Joanne K. Rowling');
    searchBook('Räuber Hotzenplotz', [], 'Otfried Preußler');

    $this->get(route('public.catalog.index', ['q' => 'Harry Poter']))->assertOk()->assertSee('Meintest du')->assertSee('Harry Potter');
    $this->get(route('public.catalog.index', ['q' => 'Raeuber Hotzenplotz']))->assertOk()->assertSee('Meintest du');
    $this->get(route('public.catalog.index', ['q' => 'Harry Potter']))->assertOk()->assertDontSee('Meintest du');

    // Ohne ähnliches Wort im Bestand gibt es keinen Vorschlag.
    $this->get(route('public.catalog.index', ['q' => 'Xylophonquartett']))->assertOk()->assertDontSee('Meintest du')->assertSee('Keine passenden Titel');
});

it('returns suggestions while typing for titles and names, at least two letters', function (): void {
    searchBook('Die unendliche Geschichte', [], 'Michael Ende');
    searchBook('Die Wilden Kerle');

    $this->getJson(route('public.catalog.suggest', ['q' => 'die u']))->assertOk()->assertJsonPath('suggestions.0', 'Die unendliche Geschichte');
    $this->getJson(route('public.catalog.suggest', ['q' => 'mic']))->assertOk()->assertJsonFragment(['Michael Ende']);
    $this->getJson(route('public.catalog.suggest', ['q' => 'd']))->assertOk()->assertExactJson(['suggestions' => []]);
    $this->getJson(route('public.catalog.suggest', ['q' => 'wil']))->assertOk()->assertJsonFragment(['Die Wilden Kerle']);

    $this->get(route('public.catalog.index'))->assertOk()->assertSee('data-suggest-url', false);
});

it('filters by age stage using the recommended minimum age', function (): void {
    searchBook('Für Kleine', ['minimum_age' => 4]);
    searchBook('Für Grundschule', ['minimum_age' => 8]);
    searchBook('Für Große', ['minimum_age' => 12]);
    searchBook('Ohne Alter');

    $this->get(route('public.catalog.index', ['alter' => '7-10']))->assertOk()->assertSee('Für Grundschule')->assertDontSee('Für Kleine')->assertDontSee('Für Große')->assertDontSee('Ohne Alter');
    $this->get(route('public.catalog.index', ['alter' => '0-6']))->assertSee('Für Kleine')->assertDontSee('Für Grundschule');
    $this->get(route('public.catalog.index', ['alter' => '14-99']))->assertDontSee('Für Große');
    $this->get(route('public.catalog.index', ['alter' => 'quatsch']))->assertSessionHasErrors('alter');
});
