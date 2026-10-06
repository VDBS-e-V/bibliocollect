<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function helpUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    app(AssignRoleAction::class)->execute($user, $role);

    return $user;
}

it('shows the help topics to everyone who works in the library', function (string $role): void {
    $user = helpUser($role);

    $this->actingAs($user)->get(route('pos.help'))->assertOk()->assertSee('Ausleihe am Tresen')->assertSee('Verwaltung');
    $this->actingAs($user)->get(route('pos.help.show', ['topic' => 'ausleihe']))
        ->assertOk()
        ->assertSee('So läuft eine Ausleihe', false)
        ->assertSee('Vorgang bestätigen')
        ->assertSee('<h2>', false)
        ->assertDontSee('<h1>Ausleihe am Tresen', false);
})->with(['student_ag_basic', 'staff', 'management']);

it('knows only the listed topics and keeps the help away from others', function (): void {
    $this->actingAs(helpUser('staff'))->get(route('pos.help.show', ['topic' => '../../.env']))->assertNotFound();
    $this->actingAs(helpUser('staff'))->get(route('pos.help.show', ['topic' => 'gibt-es-nicht']))->assertNotFound();

    $this->actingAs(helpUser('student'))->get(route('pos.help'))->assertForbidden();
    $this->actingAs(helpUser('technical_admin'))->get(route('pos.help'))->assertForbidden();
});

it('has a help text for every listed topic that is written in plain steps', function (): void {
    foreach (['ausleihe', 'katalog', 'verwaltung'] as $topic) {
        $text = (string) file_get_contents(resource_path('help/'.$topic.'.md'));

        expect(strlen($text))->toBeGreaterThan(500)->and($text)->toStartWith('# ');
    }
});
