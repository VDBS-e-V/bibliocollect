<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return redirect()->route('portal.home');
        }

        return view('identity.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('portal.home')->with('status', 'E-Mail-Adresse bestätigt.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return redirect()->route('portal.home');
        }

        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Bestätigungslink wurde erneut gesendet.');
    }
}
