<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// Email verification routes
Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
    try {
        // Find the user
        $user = \App\Models\User::find($id);

        if (!$user) {
            return redirect('https://demo.shiftend.be/?verification_error=1&message=User not found');
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return redirect('https://demo.shiftend.be/?verification_error=1&message=Email already verified');
        }

        // Verify the hash
        if (!hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        )) {
            return redirect('https://demo.shiftend.be/?verification_error=1&message=Invalid verification link');
        }

        // Mark as verified
        $user->markEmailAsVerified();

        return redirect('https://demo.shiftend.be/login?verified=1');
    } catch (\Exception $e) {
        return redirect('https://demo.shiftend.be/?verification_error=1&message=Verification failed');
    }
})->middleware(['signed'])->name('verification.verify');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// SPA route - this should be last to catch all other routes
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
