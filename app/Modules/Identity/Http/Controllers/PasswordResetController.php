<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/** Passwort vergessen: Link per E-Mail anfordern und ein neues Passwort setzen. */
final class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('identity.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        // Deaktivierte Konten bekommen keinen Link. Die Antwort ist in allen Fällen gleich, damit niemand
        // herausfinden kann, welche Adressen ein Konto haben.
        Password::sendResetLink(['email' => $data['email'], 'disabled_at' => null]);

        return redirect()
            ->route('password.request')
            ->with('status', 'Wenn zu dieser Adresse ein Konto besteht, haben wir einen Link zum Zurücksetzen geschickt. Er gilt 60 Minuten.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('identity.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $status = Password::reset(
            ['email' => $data['email'], 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token'], 'disabled_at' => null],
            static function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Wer den Link aus der Mail benutzt, hat damit auch bewiesen, dass die Adresse ihm gehört (Einladung neuer Konten).
                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.']);
        }

        return redirect()->route('login')->with('status', 'Dein Passwort wurde geändert. Du kannst dich jetzt anmelden.');
    }
}
