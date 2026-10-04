<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\ClaimOnlineAccountAction;
use App\Modules\Identity\Exceptions\InvalidPatronLinkCode;
use App\Modules\Identity\Exceptions\PatronAlreadyLinked;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ClaimAccountController extends Controller
{
    public function create(): View
    {
        return view('identity.claim-account');
    }

    public function store(Request $request, ClaimOnlineAccountAction $claim): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        try {
            $user = $claim->execute(
                code: (string) $validated['code'],
                email: (string) $validated['email'],
                password: (string) $validated['password'],
            );
        } catch (InvalidPatronLinkCode|PatronAlreadyLinked $exception) {
            throw ValidationException::withMessages([
                'code' => $exception->getMessage(),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
