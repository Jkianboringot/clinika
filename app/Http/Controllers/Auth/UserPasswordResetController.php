<?php

namespace App\Http\Controllers\Auth;

use App\Enums\IsActiveEnum;
use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserPasswordResetController extends Controller
{
    private const CODE_TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function sendCode(Request $request): RedirectResponse
    {
        $email = Str::lower($request->validate([
            'email' => ['required', 'email'],
        ])['email']);

        $user = User::where('email', $email)
            ->where('is_active', IsActiveEnum::ACTIVE->value)
            ->first();

        // Same response whether or not the email exists (or is deactivated),
        // so nobody can probe which emails are registered.
        if ($user) {
            $code = (string) random_int(100000, 999999);

            Cache::put(
                $this->cacheKey($email),
                ['hash' => Hash::make($code), 'attempts' => 0],
                now()->addMinutes(self::CODE_TTL_MINUTES),
            );

            try {
                Mail::to($user->email)->send(
                    new PasswordResetCodeMail($code, self::CODE_TTL_MINUTES)
                );
            } catch (\Throwable $e) {
                report($e);
                Cache::forget($this->cacheKey($email));

                return back()->withErrors(['email' => __('Failed to send the code. Please try again.')]);
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('If that email is registered, a code has been sent.'),
        ]);

        return back();
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $email = Str::lower($data['email']);
        $key = $this->cacheKey($email);
        $entry = Cache::get($key);

        if (! $entry) {
            return back()->withErrors(['code' => __('This code has expired. Request a new one.')]);
        }

        if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return back()->withErrors(['code' => __('Too many attempts. Request a new code.')]);
        }

        if (! Hash::check($data['code'], $entry['hash'])) {
            $entry['attempts']++;
            Cache::put($key, $entry, now()->addMinutes(self::CODE_TTL_MINUTES));

            return back()->withErrors(['code' => __('Invalid code.')]);
        }

        $user = User::where('email', $email)
            ->where('is_active', IsActiveEnum::ACTIVE->value)
            ->first();

        if (! $user) {
            return back()->withErrors(['code' => __('Invalid code.')]);
        }

        // Relies on the 'hashed' cast on User::password (default in Laravel 11).
        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();

        Cache::forget($key);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Password updated. You can now sign in.'),
        ]);

        return redirect()->route('login');
    }

    private function cacheKey(string $email): string
    {
        return 'user-password-reset:'.sha1($email);
    }
}