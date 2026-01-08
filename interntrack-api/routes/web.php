<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// -----------------------------------------------------------------------------
// Password reset UI (needed because AuthController uses Password::sendResetLink).
// SendGrid delivers a link that points to the route named "password.reset".
// -----------------------------------------------------------------------------

Route::get('/reset-password/{token}', function (string $token, Request $request) {
    $email = (string) $request->query('email', '');

    if ($email === '') {
        abort(400, 'Missing email.');
    }

    return view('auth.reset-password', [
        'token' => $token,
        'email' => $email,
    ]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            $user->tokens()->delete();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return redirect()->route('password.reset.success');
    }

    return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
});

Route::get('/reset-password/success', function () {
    return view('auth.reset-success');
})->name('password.reset.success');
