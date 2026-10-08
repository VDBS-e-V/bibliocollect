<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function legacyWebUser(string $role = 'management'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

function legacyUpload(string $fixture): UploadedFile
{
    return UploadedFile::fake()->createWithContent($fixture, (string) file_get_contents(database_path('seeders/fixtures/'.$fixture)));
}

it('takes over the legacy catalog on the web without the console: check first, then import', function (): void {
    Storage::fake('local');
    $admin = legacyWebUser();

    $this->actingAs($admin)->get(route('pos.catalog.legacy.create'))->assertOk()->assertSee('Altbestand übernehmen')->assertSee('mediaList');

    $check = $this->actingAs($admin)->post(route('pos.catalog.legacy.analyze'), [
        'media' => legacyUpload('legacy-media-demo.json'),
        'topics' => legacyUpload('legacy-topics-demo.json'),
        'signatures' => legacyUpload('legacy-signatures-demo.json'),
    ])->assertOk()->assertSee('Ergebnis der Prüfung');

    // Beim Prüfen wird nichts geschrieben.
    expect(Copy::query()->count())->toBe(0);

    preg_match('#/betrieb/katalog/altbestand/([0-9a-z]{26})#', $check->getContent(), $match);
    expect($match)->not->toBeEmpty();

    $this->actingAs($admin)->post(route('pos.catalog.legacy.commit', ['token' => $match[1]]))->assertSessionHasErrors('confirm');
    $this->actingAs($admin)->post(route('pos.catalog.legacy.commit', ['token' => $match[1]]), ['confirm' => '1'])->assertRedirect(route('pos.catalog.legacy.create'))->assertSessionHas('legacy_report');

    expect(Copy::query()->count())->toBeGreaterThan(0);
    Storage::disk('local')->assertDirectoryEmpty('legacy-imports');
});

it('imports legacy wishes once without duplicates', function (): void {
    $admin = legacyWebUser();
    $json = json_encode([
        ['type' => 'header', 'version' => '4.9.11'],
        ['type' => 'table', 'name' => 'bookWishes', 'data' => [
            ['id' => '1', 'user_id' => '2147483647', 'name' => '', 'email' => '', 'title' => 'Der Grüffelo', 'author' => 'Julia Donaldson', 'isbn' => '9783407792914', 'note' => '', 'created_at' => '2026-01-22 01:04:52'],
            ['id' => '2', 'user_id' => '1', 'name' => 'Mia', 'email' => 'mia@example.invalid', 'title' => 'Ohne ISBN', 'author' => 'A. Autor', 'isbn' => '', 'note' => 'bitte', 'created_at' => '2026-01-23 10:00:00'],
            ['id' => '3', 'title' => '', 'author' => '', 'isbn' => ''],
        ]],
    ], JSON_THROW_ON_ERROR);

    $send = fn () => $this->actingAs($admin)->post(route('pos.catalog.legacy.wishes'), ['wishes' => UploadedFile::fake()->createWithContent('bookWishes.json', $json)]);

    $send()->assertSessionHas('legacy_wishes', '2 Buchwünsche übernommen, 1 übersprungen (schon vorhanden oder ohne Titel).');
    $send()->assertSessionHas('legacy_wishes', '0 Buchwünsche übernommen, 3 übersprungen (schon vorhanden oder ohne Titel).');

    $wish = BookWish::query()->where('isbn', '9783407792914')->firstOrFail();

    expect(BookWish::query()->count())->toBe(2)
        ->and($wish->patron_id)->toBeNull()
        ->and($wish->created_at?->format('Y-m-d'))->toBe('2026-01-22')
        ->and(BookWish::query()->where('title', 'Ohne ISBN')->firstOrFail()->contact_email)->toBe('mia@example.invalid');
});

it('keeps the legacy import away from people without catalog import permission', function (): void {
    $this->actingAs(legacyWebUser('student_ag_extended'))->get(route('pos.catalog.legacy.create'))->assertForbidden();
});
