<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Support\Transliteration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function translitBook(string $title, ?string $author = null, string $language = 'rus'): Title
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create(['title_id' => $titleModel->getKey(), 'media_type' => 'book', 'language_code' => $language]);
    Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => md5($title), 'status' => 'active']);

    if ($author !== null) {
        $contributor = Contributor::query()->create(['display_name' => $author]);
        TitleContribution::query()->create(['title_id' => $titleModel->getKey(), 'contributor_id' => $contributor->getKey(), 'role_key' => 'author']);
    }

    return $titleModel;
}

it('transliterates Cyrillic, Greek, Arabic and special Latin letters', function (): void {
    expect(Transliteration::latin('Война и мир'))->toBe('voina i mir')
        ->and(Transliteration::latin('Лев Толстой'))->toBe('lev tolstoi')
        ->and(Transliteration::latin('Щука'))->toBe('shchuka')
        ->and(Transliteration::latin('Їжак'))->toBe('iizhak')
        ->and(Transliteration::latin('Οδύσσεια'))->toBe('odisseia')
        ->and(Transliteration::latin('Çocuk Kitabı'))->toBe('cocuk kitabi')
        ->and(Transliteration::latin('Łódź'))->toBe('lodz')
        ->and(Transliteration::latin('كتاب الأطفال'))->toBe('ktab alatfal');
});

it('creates aliases only for text that needs them and adds a vowel-less form for Arabic', function (): void {
    expect(Transliteration::aliases('Räuber Hotzenplotz', 'Ein Buch'))->toBeNull()
        ->and(Transliteration::aliases('Café'))->toBeNull()
        ->and(Transliteration::aliases(null, ''))->toBeNull()
        ->and(Transliteration::aliases('Война и мир'))->toBe('voina i mir')
        ->and(Transliteration::aliases('Çocuk Kitabı', 'Ein Untertitel'))->toBe('cocuk kitabi')
        ->and(Transliteration::aliases('كتاب الأطفال'))->toBe('ktab alatfal | ktb ltfl')
        ->and(Transliteration::needs('東京物語'))->toBeTrue();

    expect(Transliteration::searchForms('Voyna'))->toBe(['voina'])
        ->and(Transliteration::searchForms('kitab'))->toBe(['kitab', 'ktb'])
        ->and(Transliteration::searchForms('al'))->toBe(['al'])
        ->and(Transliteration::searchForms('Tolstoj'))->toBe(['tolstoi', 'tlst']);
});

it('stores the transliteration when a title or a contributor is saved and updates it', function (): void {
    $title = translitBook('Война и мир', 'Лев Толстой');

    expect($title->refresh()->search_aliases)->toBe('voina i mir')
        ->and(Contributor::query()->firstOrFail()->search_aliases)->toBe('lev tolstoi');

    $title->update(['preferred_title' => 'Anna Karenina']);
    expect($title->refresh()->search_aliases)->toBeNull();
});

it('finds books and authors in other scripts with Latin letters in the public catalog', function (): void {
    translitBook('Война и мир', 'Лев Толстой');
    translitBook('Çocuk Kitabı', 'Ayşe Yılmaz', 'tur');
    translitBook('كتاب الأطفال', 'سمير', 'ara');
    translitBook('Ein deutsches Buch', 'Anna Autorin', 'ger');

    foreach (['voyna' => 'Война и мир', 'Woina' => 'Война и мир', 'tolstoj' => 'Война и мир', 'cocuk' => 'Çocuk Kitabı', 'yilmaz' => 'Çocuk Kitabı', 'kitabi' => 'Çocuk Kitabı', 'kitab' => 'كتاب الأطفال', 'ktab alatfal' => 'كتاب الأطفال'] as $query => $expected) {
        $this->get(route('public.catalog.index', ['q' => $query]))->assertOk()->assertSee($expected);
    }

    // Die Originalschrift wird weiterhin gefunden, und ein deutsches Buch taucht bei der Umschrift nicht auf.
    $this->get(route('public.catalog.index', ['q' => 'Война']))->assertOk()->assertSee('Война и мир');
    $this->get(route('public.catalog.index', ['q' => 'voyna']))->assertOk()->assertDontSee('Ein deutsches Buch');
});

it('backfills the transliteration for existing rows in the migration', function (): void {
    translitBook('Война и мир', 'Лев Толстой');
    translitBook('Ein deutsches Buch', 'Anna Autorin', 'ger');

    $migration = require base_path('app/Modules/Catalog/database/migrations/2026_10_11_100000_add_search_aliases_for_transliteration.php');
    $migration->down();
    $migration->up();

    expect(DB::table('catalog_titles')->where('preferred_title', 'Война и мир')->value('search_aliases'))->toBe('voina i mir')
        ->and(DB::table('catalog_titles')->where('preferred_title', 'Ein deutsches Buch')->value('search_aliases'))->toBeNull()
        ->and(DB::table('catalog_contributors')->where('display_name', 'Лев Толстой')->value('search_aliases'))->toBe('lev tolstoi');
});
