<?php

declare(strict_types=1);

use App\Foundation\Update\UpdateManager;
use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

function updateUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir().'/bc-update-page-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->dir);
    config(['foundation.update.directory' => $this->dir]);
});

afterEach(function (): void {
    File::deleteDirectory($this->dir);
});

it('shows the update page only to those who may update', function (): void {
    foreach (['management', 'technical_admin'] as $role) {
        $this->actingAs(updateUser($role))->get(route('administration.update.index'))->assertOk()->assertSee('Neue Version von GitHub holen')->assertSee('Paket selbst bereitstellen')->assertSee('Nachts automatisch einspielen');
    }

    $this->actingAs(updateUser('staff'))->get(route('administration.update.index'))->assertForbidden();
    $this->actingAs(updateUser('student_ag_extended'))->get(route('administration.update.index'))->assertForbidden();
});

it('uploads a package, lists it, switches the night update and deletes the package', function (): void {
    $admin = updateUser('management');
    $zip = tempnam(sys_get_temp_dir(), 'pkg');
    $archive = new ZipArchive;
    $archive->open($zip, ZipArchive::OVERWRITE);

    foreach (['artisan', 'composer.json', 'bootstrap/app.php', 'vendor/autoload.php', 'public/index.php'] as $file) {
        $archive->addFromString($file, '<?php');
    }

    $archive->addFromString('VERSION', 'v9.9.9');
    $archive->close();

    $this->actingAs($admin)->post(route('administration.update.upload'), ['package' => UploadedFile::fake()->createWithContent('Neue Version.zip', (string) file_get_contents($zip))])->assertRedirect(route('administration.update.index'))->assertSessionHas('update_success');

    $this->actingAs($admin)->get(route('administration.update.index'))->assertSee('Neue_Version.zip')->assertSee('v9.9.9')->assertSee('Version nicht vergleichbar');

    $this->actingAs($admin)->post(route('administration.update.upload'), ['package' => UploadedFile::fake()->createWithContent('kaputt.zip', 'kein zip')])->assertSessionHasErrors('package');

    $this->actingAs($admin)->post(route('administration.update.auto'), ['auto' => '1'])->assertSessionHas('update_success');
    expect(app(UpdateManager::class)->autoEnabled())->toBeTrue();
    $this->actingAs($admin)->post(route('administration.update.auto'), ['auto' => '0']);
    expect(app(UpdateManager::class)->autoEnabled())->toBeFalse();

    $this->actingAs($admin)->post(route('administration.update.apply'), ['package' => 'Neue_Version.zip'])->assertSessionHasErrors('confirm');

    $this->actingAs($admin)->delete(route('administration.update.destroy', ['name' => 'Neue_Version.zip']))->assertSessionHas('update_success');
    expect(File::glob($this->dir.'/*.zip'))->toBe([]);
});

it('hands over to the finish address after applying', function (): void {
    $admin = updateUser('management');
    $token = str_repeat('a', 40);

    $this->mock(UpdateManager::class, function ($mock) use ($token): void {
        $mock->shouldReceive('apply')->once()->with('x.zip', false)->andReturn($token);
    });

    $this->actingAs($admin)->post(route('administration.update.apply'), ['package' => 'x.zip', 'confirm' => '1'])->assertRedirect(route('update.finish', ['token' => $token]));
});
