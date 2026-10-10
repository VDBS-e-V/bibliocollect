<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Circulation\Models\ReadingList;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Privacy\Services\AnonymizationService;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function listUser(string $role, ?SchoolClass $class = null, string $number = '810001'): User
{
    $patron = Patron::query()->create([
        'library_number' => $number,
        'kind' => $role === 'teacher' ? PatronKind::Teacher : PatronKind::Student,
        'status' => PatronStatus::Active,
        'first_name' => 'Test',
        'last_name' => 'Person'.$number,
        'birth_date' => '2000-01-01',
        'school_class_id' => $class?->getKey(),
    ]);
    $user = User::factory()->create(['email_verified_at' => now(), 'patron_id' => $patron->getKey()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function listClass(string $name): SchoolClass
{
    $year = SchoolYear::query()->first() ?? SchoolYear::query()->create(['name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-07-31', 'is_active' => true]);

    return SchoolClass::query()->create(['school_year_id' => $year->getKey(), 'name' => $name, 'grade_level' => 7, 'is_active' => true]);
}

function listTitle(string $name, string $status = 'active'): Title
{
    $title = Title::query()->create(['preferred_title' => $name, 'sort_title' => $name]);
    $edition = Edition::query()->create(['title_id' => $title->getKey(), 'media_type' => 'book']);
    Copy::query()->create(['edition_id' => $edition->getKey(), 'barcode' => md5($name), 'status' => $status]);

    return $title;
}

it('lets a teacher create a list, add titles from search and bookmarks and remove them again', function (): void {
    $class = listClass('7a');
    $second = listClass('7b');
    $teacher = listUser('teacher');
    $found = listTitle('Der kleine Wal');
    $marked = listTitle('Sternenreise');
    Bookmark::query()->create(['user_id' => $teacher->getKey(), 'title_id' => $marked->getKey()]);

    $this->post(route('portal.reading-lists.store'), ['name' => 'x'])->assertRedirect(route('login'));

    $this->actingAs($teacher)->get(route('portal.reading-lists'))->assertOk()->assertSee('Neue Leseliste');

    $this->actingAs($teacher)->post(route('portal.reading-lists.store'), ['name' => 'Klassenlektüre', 'school_class_ids' => [$class->getKey(), $second->getKey()], 'is_published' => '1'])
        ->assertRedirect();
    $list = ReadingList::query()->firstOrFail();
    expect($list->name)->toBe('Klassenlektüre')->and($list->is_published)->toBeTrue()->and($list->classes()->count())->toBe(2)->and(strlen($list->public_token))->toBe(32);

    $this->actingAs($teacher)->get(route('portal.reading-lists.show', ['listId' => $list->getKey(), 'q' => 'Wal']))
        ->assertOk()->assertSee('Der kleine Wal')->assertSee('Von meiner Merkliste')->assertSee('Sternenreise');

    $this->actingAs($teacher)->post(route('portal.reading-lists.add', ['listId' => $list->getKey(), 'titleId' => $found->getKey()]))->assertSessionHas('portal_success');
    $this->actingAs($teacher)->post(route('portal.reading-lists.add', ['listId' => $list->getKey(), 'titleId' => $marked->getKey()]));
    $this->actingAs($teacher)->post(route('portal.reading-lists.add', ['listId' => $list->getKey(), 'titleId' => $marked->getKey()]));
    expect($list->items()->count())->toBe(2);

    $this->actingAs($teacher)->post(route('portal.reading-lists.remove', ['listId' => $list->getKey(), 'titleId' => $found->getKey()]));
    expect($list->items()->count())->toBe(1);

    $this->actingAs($teacher)->post(route('portal.reading-lists.update', ['listId' => $list->getKey()]), ['name' => 'Umbenannt'])->assertSessionHasNoErrors();
    expect($list->refresh()->name)->toBe('Umbenannt')->and($list->is_published)->toBeFalse();

    $this->actingAs($teacher)->post(route('portal.reading-lists.destroy', ['listId' => $list->getKey()]))->assertRedirect(route('portal.reading-lists'));
    expect(ReadingList::query()->count())->toBe(0);
});

it('shows a published list only to students of the class and only while it runs', function (): void {
    $class = listClass('7a');
    $otherClass = listClass('7b');
    $teacher = listUser('teacher');
    $student = listUser('student', $class, '810002');
    $stranger = listUser('student', $otherClass, '810003');
    $classless = listUser('student', null, '810004');
    $title = listTitle('Lesestoff');

    $list = ReadingList::query()->create(['user_id' => $teacher->getKey(), 'name' => 'Sommerlektüre', 'is_published' => true]);
    $list->classes()->sync([$class->getKey()]);
    $list->items()->create(['title_id' => $title->getKey()]);

    $this->actingAs($student)->get(route('portal.reading-lists'))->assertOk()->assertSee('Sommerlektüre')->assertDontSee('Neue Leseliste');
    $this->actingAs($student)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertOk()->assertSee('Lesestoff')->assertDontSee('Angaben zur Liste');
    $this->actingAs($stranger)->get(route('portal.reading-lists'))->assertOk()->assertDontSee('Sommerlektüre');
    $this->actingAs($stranger)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertNotFound();
    $this->actingAs($classless)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertNotFound();

    // Schüler:innen dürfen nichts verändern.
    $this->actingAs($student)->post(route('portal.reading-lists.store'), ['name' => 'Eigene'])->assertForbidden();
    $this->actingAs($student)->post(route('portal.reading-lists.add', ['listId' => $list->getKey(), 'titleId' => $title->getKey()]))->assertForbidden();
    $this->actingAs($student)->post(route('portal.reading-lists.destroy', ['listId' => $list->getKey()]))->assertForbidden();

    // Ausgeschaltet oder abgelaufen: unsichtbar für die Klasse, die Lehrkraft sieht sie weiter.
    $list->update(['is_published' => false]);
    $this->actingAs($student)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertNotFound();
    $this->actingAs($teacher)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertOk()->assertSee('Liste ist nicht erreichbar');

    $list->update(['is_published' => true, 'ends_on' => now()->subDay()->toDateString()]);
    $this->actingAs($student)->get(route('portal.reading-lists'))->assertDontSee('Sommerlektüre');
    $this->actingAs($student)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertNotFound();
});

it('keeps lists private between teachers and hides withdrawn books', function (): void {
    $class = listClass('7a');
    $first = listUser('teacher', null, '810005');
    $second = listUser('teacher', null, '810006');
    $kept = listTitle('Bleibt');
    $gone = listTitle('Weg');

    $list = ReadingList::query()->create(['user_id' => $first->getKey(), 'name' => 'Erste Liste', 'is_published' => true]);
    $list->classes()->sync([$class->getKey()]);
    $list->items()->create(['title_id' => $kept->getKey()]);
    $list->items()->create(['title_id' => $gone->getKey()]);

    $this->actingAs($second)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertNotFound();
    $this->actingAs($second)->post(route('portal.reading-lists.destroy', ['listId' => $list->getKey()]))->assertNotFound();
    $this->actingAs($second)->get(route('portal.reading-lists'))->assertDontSee('Erste Liste');

    Copy::query()->where('barcode', md5('Weg'))->update(['status' => 'withdrawn']);
    $this->actingAs($first)->get(route('portal.reading-lists.show', ['listId' => $list->getKey()]))->assertOk()->assertSee('Bleibt')->assertDontSee('Weg');
});

it('deletes the lists of a teacher when the account is anonymized', function (): void {
    $teacher = listUser('teacher', null, '810007');
    ReadingList::query()->create(['user_id' => $teacher->getKey(), 'name' => 'Weg damit']);

    $patron = Patron::query()->findOrFail($teacher->patron_id);
    $patron->forceFill(['status' => PatronStatus::Departed, 'leaving_on' => now()->subDays(5)->toDateString()])->save();
    app(AnonymizationService::class)->anonymizePatron($patron->refresh());

    expect(ReadingList::query()->count())->toBe(0);
});

it('opens a list through the public link without an account and can renew the link', function (): void {
    $class = listClass('7a');
    $otherClass = listClass('7b');
    $teacher = listUser('teacher', null, '810008');
    $title = listTitle('Link-Buch');
    $gone = listTitle('Link-Weg');

    $list = ReadingList::query()->create(['user_id' => $teacher->getKey(), 'name' => 'Linkliste', 'description' => 'Für beide Klassen', 'is_published' => true]);
    $list->classes()->sync([$class->getKey(), $otherClass->getKey()]);
    $list->items()->create(['title_id' => $title->getKey()]);
    $list->items()->create(['title_id' => $gone->getKey()]);
    Copy::query()->where('barcode', md5('Link-Weg'))->update(['status' => 'withdrawn']);

    $url = route('public.reading-list', ['token' => $list->public_token]);
    $this->get($url)->assertOk()->assertSee('Linkliste')->assertSee('Link-Buch')->assertDontSee('Link-Weg')->assertSee('7a')->assertSee('7b')
        ->assertSee('Zum Merken anmelden')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    // Ein Link, den es nicht gibt, ist 404; die Liste selbst nennt keine Zahlen-ID als Zugang.
    $this->get(route('public.reading-list', ['token' => str_repeat('a', 32)]))->assertNotFound();
    $this->get(route('public.reading-list', ['token' => $list->getKey()]))->assertNotFound();

    // Beide Klassen sehen die Liste im Konto.
    foreach ([['810009', $class], ['810010', $otherClass]] as [$number, $studentClass]) {
        $student = listUser('student', $studentClass, $number);
        $this->actingAs($student)->get(route('portal.reading-lists'))->assertOk()->assertSee('Linkliste');
    }

    // Link erneuern: der alte geht nicht mehr. Nur die Besitzerin darf das.
    $old = $list->public_token;
    $this->actingAs(listUser('teacher', null, '810011'))->post(route('portal.reading-lists.renew-link', ['listId' => $list->getKey()]))->assertNotFound();
    $this->actingAs($teacher)->post(route('portal.reading-lists.renew-link', ['listId' => $list->getKey()]))->assertRedirect();
    expect($list->refresh()->public_token)->not->toBe($old);
    $this->get(route('public.reading-list', ['token' => $old]))->assertNotFound();
    $this->get(route('public.reading-list', ['token' => $list->public_token]))->assertOk();

    // Ausgeschaltet oder abgelaufen: auch der Link zeigt nichts mehr.
    $list->update(['is_published' => false]);
    $this->get(route('public.reading-list', ['token' => $list->public_token]))->assertNotFound();
    $list->update(['is_published' => true, 'ends_on' => now()->subDay()->toDateString()]);
    $this->get(route('public.reading-list', ['token' => $list->public_token]))->assertNotFound();
});
