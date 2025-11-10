<?php

use Illuminate\Support\Facades\Route;
use NagibMahfuj\LaravelSecurityPolicies\Http\Controllers\MfaController;

Route::middleware(['web'])->group(function () {
    Route::get('/mfa/verify', [MfaController::class, 'showVerify'])->name('security.mfa.verify');
    Route::post('/mfa/verify', [MfaController::class, 'verify'])->name('security.mfa.verify.post');
    Route::post('/mfa/resend', [MfaController::class, 'resend'])->name('security.mfa.resend');
});
