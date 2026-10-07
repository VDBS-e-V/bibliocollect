<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Models\User;
use App\Modules\Identity\Actions\AssignRoleAction;
use App\Modules\Identity\Models\UserRoleAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Einrichtungsseite für Hosting ohne SSH. Jede Aktion verlangt das Token aus `SETUP_TOKEN`; die Seite benötigt
 * keine Sitzung und keine Datenbank, damit sie auch vor der ersten Migration funktioniert.
 */
final class SetupController
{
    public function show(): Response
    {
        return $this->page();
    }

    public function run(Request $request, string $action, AssignRoleAction $assign): Response
    {
        $token = (string) $request->input('token', '');

        if (! hash_equals((string) config('hosting.setup_token'), $token)) {
            return $this->page('Das Token stimmt nicht.', true, 403);
        }

        try {
            return match ($action) {
                'migrate' => $this->artisan('migrate', ['--force' => true], 'Migrationen ausgeführt.'),
                'doctor' => $this->artisan('app:doctor', [], 'Prüfung abgeschlossen.'),
                'recover' => $this->recoverAdmin($request, $assign),
                default => $this->createAdmin($request, $assign),
            };
        } catch (Throwable $exception) {
            return $this->page('Fehler: '.$exception->getMessage(), true, 500);
        }
    }

    /** @param  array<string, mixed>  $arguments */
    private function artisan(string $command, array $arguments, string $message): Response
    {
        $status = Artisan::call($command, $arguments);

        return $this->page($message.($status === 0 ? '' : ' (mit Fehlern, Exit-Code '.$status.')'), $status !== 0, 200, trim(Artisan::output()));
    }

    /** Notfall: Ein ausgesperrtes Verwaltungskonto bekommt ein neues Passwort, ist wieder aktiv und hat die Rolle Verwaltung. */
    private function recoverAdmin(Request $request, AssignRoleAction $assign): Response
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:200'],
        ], ['password.min' => 'Das Passwort braucht mindestens 12 Zeichen.']);

        if ($validator->fails()) {
            return $this->page((string) $validator->errors()->first(), true, 422);
        }

        $data = $validator->validated();
        $user = User::query()->where('email', mb_strtolower(trim($data['email'])))->first();

        if (! $user instanceof User) {
            return $this->page('Zu dieser E-Mail-Adresse gibt es kein Konto.', true, 404);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'disabled_at' => null,
            'disabled_reason' => null,
            'remember_token' => null,
        ])->save();

        if (! UserRoleAssignment::query()->where('user_id', $user->getKey())->where('role_key', 'management')->exists()) {
            $assign->execute($user, 'management');
        }

        return $this->page('Das Konto '.$user->email.' ist wiederhergestellt: neues Passwort, aktiv, Rolle Verwaltung. Leere danach SETUP_TOKEN in der .env.');
    }

    private function createAdmin(Request $request, AssignRoleAction $assign): Response
    {
        if (UserRoleAssignment::query()->where('role_key', 'management')->exists()) {
            return $this->page('Es gibt bereits ein Konto mit der Rolle Verwaltung. Weitere Konten legst du in der Anwendung an.', true, 409);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:200'],
        ], [
            'password.min' => 'Das Passwort braucht mindestens 12 Zeichen.',
            'email.unique' => 'Diese E-Mail-Adresse ist schon vergeben.',
        ]);

        if ($validator->fails()) {
            return $this->page((string) $validator->errors()->first(), true, 422);
        }

        $data = $validator->validated();

        $user = new User;
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ])->save();

        $assign->execute($user, 'management');

        return $this->page('Das Verwaltungskonto wurde angelegt. Du kannst dich jetzt anmelden. Leere danach SETUP_TOKEN in der .env.');
    }

    private function page(?string $message = null, bool $error = false, int $status = 200, ?string $output = null): Response
    {
        $e = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<meta name="robots" content="noindex"><title>Einrichtung</title>'
            .'<style>body{font:16px/1.5 system-ui,sans-serif;max-width:44rem;margin:2rem auto;padding:0 1rem;color:#111}'
            .'form{border:1px solid #bbb;padding:1rem;margin:1rem 0}label{display:block;margin:.5rem 0 .2rem;font-weight:600}'
            .'input{width:100%;padding:.4rem;box-sizing:border-box}button{margin-top:.8rem;padding:.5rem 1rem}'
            .'.msg{padding:.6rem 1rem;border-left:4px solid #1a7f37;background:#eef8f0}.err{border-color:#b3261e;background:#fdeceb}'
            .'pre{background:#f4f4f4;padding:1rem;overflow:auto;font-size:13px}</style></head><body>'
            .'<h1>BiblioCollect einrichten</h1>'
            .'<p>Diese Seite gibt es nur, solange <code>SETUP_TOKEN</code> in der <code>.env</code> gesetzt ist. Nach der Einrichtung bitte leeren.</p>';

        if ($message !== null) {
            $html .= '<p class="msg'.($error ? ' err' : '').'" role="status">'.$e($message).'</p>';
        }

        if ($output !== null && $output !== '') {
            $html .= '<pre>'.$e($output).'</pre>';
        }

        $field = '<label for="%1$s-token">Token (SETUP_TOKEN)</label><input id="%1$s-token" name="token" type="password" required autocomplete="off">';

        $html .= '<form method="post" action="/_setup/migrate">'.csrf_field().'<h2>1. Datenbank anlegen</h2>'.sprintf($field, 'migrate').'<button type="submit">Migrationen ausführen</button></form>'
            .'<form method="post" action="/_setup/admin">'.csrf_field().'<h2>2. Verwaltungskonto anlegen</h2>'.sprintf($field, 'admin')
            .'<label for="admin-name">Name</label><input id="admin-name" name="name" required>'
            .'<label for="admin-email">E-Mail</label><input id="admin-email" name="email" type="email" required>'
            .'<label for="admin-password">Passwort (mindestens 12 Zeichen)</label><input id="admin-password" name="password" type="password" minlength="12" required autocomplete="new-password">'
            .'<button type="submit">Konto anlegen</button></form>'
            .'<form method="post" action="/_setup/recover">'.csrf_field().'<h2>Notfall: Zugang wiederherstellen</h2><p>Für ein ausgesperrtes Verwaltungskonto: setzt ein neues Passwort, aktiviert das Konto und vergibt die Rolle Verwaltung.</p>'.sprintf($field, 'recover')
            .'<label for="recover-email">E-Mail des Kontos</label><input id="recover-email" name="email" type="email" required>'
            .'<label for="recover-password">Neues Passwort (mindestens 12 Zeichen)</label><input id="recover-password" name="password" type="password" minlength="12" required autocomplete="new-password">'
            .'<button type="submit">Zugang wiederherstellen</button></form>'
            .'<form method="post" action="/_setup/doctor">'.csrf_field().'<h2>3. Einrichtung prüfen</h2>'.sprintf($field, 'doctor').'<button type="submit">Prüfen</button></form>'
            .'</body></html>';

        return response($html, $status, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
