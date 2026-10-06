<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\ClaimAccountController;
use App\Modules\Identity\Http\Controllers\EmailVerificationController;
use App\Modules\Identity\Http\Controllers\LoginController;
use App\Modules\Identity\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/anmelden', [LoginController::class, 'create'])->name('login');
        Route::post('/anmelden', [LoginController::class, 'store'])->name('login.store');

        Route::get('/passwort-vergessen', [PasswordResetController::class, 'create'])->name('password.request');
        Route::post('/passwort-vergessen', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
        Route::get('/passwort-zuruecksetzen/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/passwort-zuruecksetzen', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');

        Route::get('/konto-aktivieren', [ClaimAccountController::class, 'create'])->name('identity.claim.create');
        Route::post('/konto-aktivieren', [ClaimAccountController::class, 'store'])->name('identity.claim.store');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/abmelden', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/email-verifizieren', [EmailVerificationController::class, 'notice'])
            ->name('verification.notice');
        Route::get('/email-verifizieren/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('/email-verifizieren/erneut-senden', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });
});
