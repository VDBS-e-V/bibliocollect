<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Series;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\SeriesService;
use App\Modules\Catalog\Support\SeriesParser;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seriesBook(string $title, ?string $statement, ?string $publisher = null, string $status = 'active'): Edition
{
    $titleModel = Title::query()->create(['preferred_title' => $title, 'sort_title' => $title]);
    $edition = Edition::query()->create([
        'title_id' => $titleModel->getKey(),
        'media_type' => 'book',
        'series_statement' => $statement,
        'publisher_name' => $publisher,
    ]);
    Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => md5($title), 'status' => $status]);

    return $edition;
}

it('splits series statements into name and volume and ignores plain numbers', function (): void {
    expect(SeriesParser::parse('Die Schule der magischen Tiere ; 3'))->toMatchArray(['name' => 'Die Schule der magischen Tiere', 'volume' => '3'])
        ->and(SeriesParser::parse('Was ist was, Band 12'))->toMatchArray(['name' => 'Was ist was', 'volume' => '12'])
        ->and(SeriesParser::parse('Schriftenreihe der Bundeszentrale für politische Bildung , Band 1668'))->toMatchArray(['volume' => '1668'])
        ->and(SeriesParser::parse('Harry Potter 4'))->toMatchArray(['name' => 'Harry Potter', 'volume' => '4'])
        ->and(SeriesParser::parse('Alles vom Sams'))->toMatchArray(['name' => 'Alles vom Sams', 'volume' => null])
        ->and(SeriesParser::parse('1001 Abenteuer'))->toMatchArray(['name' => '1001 Abenteuer', 'volume' => null])
        ->and(SeriesParser::parse('[Fischer]'))->toMatchArray(['name' => 'Fischer'])
        ->and(SeriesParser::parse('126'))->toBeNull()
        ->and(SeriesParser::parse('  '))->toBeNull()
        ->and(SeriesParser::parse(null))->toBeNull()
        ->and(SeriesParser::key('Gullivers Bu?cher'))->toBe(SeriesParser::key('Gullivers Bücher'))
        ->and(SeriesParser::key("Su\u{0308}ddeutsche Zeitung"))->toBe(SeriesParser::key('Süddeutsche Zeitung'))
        ->and(SeriesParser::volumeNumber('3-4'))->toBe(3)
        ->and(SeriesParser::volumeNumber(null))->toBeNull();
});

it('assigns editions to series when saved and merges spelling variants', function (): void {
    $a = seriesBook('Band eins', 'Die Wilden Kerle ; 1');
    $b = seriesBook('Band zwei', 'Die Wilden Kerle ; Bd. 2');
    $c = seriesBook('Gulli 1', 'Gullivers Bu?cher');
    $d = seriesBook('Gulli 2', 'Gullivers Bücher');
    $plain = seriesBook('Ohne', null);

    expect($a->series_id)->not->toBeNull()->and($a->series_id)->toBe($b->series_id)->and($a->series_volume)->toBe('1')->and($b->series_volume)->toBe('2')
        ->and($c->series_id)->toBe($d->series_id)->and($plain->series_id)->toBeNull();
    expect(Series::query()->count())->toBe(2);
    expect(Series::query()->findOrFail($c->series_id)->name)->toBe('Gullivers Bücher')->and(Series::query()->findOrFail($c->series_id)->is_hidden)->toBeTrue();

    // Ändert sich die Angabe, wandert die Ausgabe in die neue Reihe.
    $a->update(['series_statement' => 'Vampirschwestern ; 7']);
    expect($a->refresh()->series->name)->toBe('Vampirschwestern')->and($a->series_volume)->toBe('7');

    // Verlagsreihen sind von Anfang an ausgeblendet, echte Reihen nicht.
    $imprint = seriesBook('Taschenbuch', 'dtv', 'Deutscher Taschenbuch Verlag');
    expect($imprint->series->is_hidden)->toBeTrue()->and($a->series->is_hidden)->toBeFalse();
});

it('shows the series page ordered by volume with availability and hides publisher lines', function (): void {
    seriesBook('Drittes Abenteuer', 'Die Abenteuerreihe ; 3');
    seriesBook('Erstes Abenteuer', 'Die Abenteuerreihe ; 1');
    seriesBook('Zehntes Abenteuer', 'Die Abenteuerreihe ; 10');
    seriesBook('Weg', 'Die Abenteuerreihe ; 4', null, 'withdrawn');
    seriesBook('Ein dtv-Buch', 'dtv');

    $series = Series::query()->where('name', 'Die Abenteuerreihe')->firstOrFail();
    $html = $this->get(route('public.series', ['slug' => $series->slug]))->assertOk()->assertSee('Die Abenteuerreihe')->assertDontSee('Weg')->getContent();

    expect(strpos($html, 'Erstes Abenteuer'))->toBeLessThan(strpos($html, 'Drittes Abenteuer'))
        ->and(strpos($html, 'Drittes Abenteuer'))->toBeLessThan(strpos($html, 'Zehntes Abenteuer'));

    $hidden = Series::query()->where('name', 'dtv')->firstOrFail();
    $this->get(route('public.series', ['slug' => $hidden->slug]))->assertNotFound();
    $this->get(route('public.series', ['slug' => 'gibt-es-nicht']))->assertNotFound();

    $this->get(route('public.series.index'))->assertOk()->assertSee('Die Abenteuerreihe')->assertDontSee('dtv');

    $title = Title::query()->where('preferred_title', 'Erstes Abenteuer')->firstOrFail();
    $this->get(route('public.catalog.show', ['titleId' => $title->getKey()]))->assertOk()->assertSee('Teil der Reihe')->assertSee('Band 1');
});

it('names the next volume for a reader who has borrowed earlier ones', function (): void {
    $first = seriesBook('Zauber eins', 'Zauberreihe ; 1');
    seriesBook('Zauber zwei', 'Zauberreihe ; 2');
    seriesBook('Zauber drei', 'Zauberreihe ; 3');
    $series = Series::query()->where('name', 'Zauberreihe')->firstOrFail();

    $patron = Patron::query()->create(['library_number' => '910001', 'kind' => PatronKind::Student, 'status' => PatronStatus::Active, 'first_name' => 'Lea', 'last_name' => 'Leserin', 'birth_date' => '2012-01-01']);
    $user = User::factory()->create(['email_verified_at' => now(), 'patron_id' => $patron->getKey()]);
    app(AssignRoleAction::class)->execute($user, 'student');

    $this->actingAs($user)->get(route('public.series', ['slug' => $series->slug]))->assertOk()->assertDontSee('Nächster Band');

    $copy = Copy::query()->where('edition_id', $first->getKey())->firstOrFail();
    Loan::query()->create(['patron_id' => $patron->getKey(), 'copy_id' => $copy->getKey(), 'checked_out_at' => now()->subDays(10), 'due_on' => now()->addDays(4)->toDateString(), 'returned_at' => now()->subDay()]);

    $this->actingAs($user)->get(route('public.series', ['slug' => $series->slug]))->assertOk()->assertSee('Nächster Band')->assertSee('Zauber zwei (Band 2)');
});

it('lets staff review, hide and rename series and recompute assignments', function (): void {
    seriesBook('Reihenbuch', 'Testreihe ; 1');
    $series = Series::query()->where('name', 'Testreihe')->firstOrFail();

    $staff = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($staff, 'staff');
    $student = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($student, 'student');

    $this->actingAs($student)->get(route('pos.catalog.series'))->assertForbidden();
    $this->actingAs($staff)->get(route('pos.catalog.series'))->assertOk()->assertSee('Testreihe')->assertSee('Auswertung');

    $this->actingAs($staff)->post(route('pos.catalog.series.update', ['seriesId' => $series->getKey()]), ['name' => 'Testreihe neu', 'is_hidden' => '1'])->assertSessionHasNoErrors();
    expect($series->refresh()->name)->toBe('Testreihe neu')->and($series->is_hidden)->toBeTrue();

    Edition::query()->update(['series_id' => null, 'series_volume' => null]);
    app(SeriesService::class)->syncAll();
    expect(Edition::query()->whereNotNull('series_id')->count())->toBe(1);
    $this->actingAs($staff)->post(route('pos.catalog.series.sync'))->assertSessionHas('catalog_success');
});
