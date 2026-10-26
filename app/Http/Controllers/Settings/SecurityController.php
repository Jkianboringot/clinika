<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Mail\PasswordResetCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    private const CODE_TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $props = [
            'passwordRules' => Password::defaults(),
        ];

        return Inertia::render('settings/security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }

    /**
     * Email a random 6-digit code to the current user.
     */
    public function sendResetCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->email) {
            return back()->withErrors(['email' => __('Your account has no email address.')]);
        }

        $code = (string) random_int(100000, 999999);

        Cache::put(
            $this->cacheKey($user->id),
            ['hash' => Hash::make($code), 'attempts' => 0],
            now()->addMinutes(self::CODE_TTL_MINUTES),
        );

        try {
            Mail::to($user->email)->send(new PasswordResetCodeMail($code, self::CODE_TTL_MINUTES));
        } catch (\Throwable $e) {
            Cache::forget($this->cacheKey($user->id));

            return back()->withErrors(['email' => __('Failed to send the code. Please try again.')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('A code was sent to your email.')]);

        return back();
    }

    /**
     * Verify the code and set the new password.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $key = $this->cacheKey($user->id);
        $entry = Cache::get($key);

        if (! $entry) {
            return back()->withErrors(['code' => __('This code has expired. Request a new one.')]);
        }

        if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return back()->withErrors(['code' => __('Too many attempts. Request a new code.')]);
        }

        if (! Hash::check($request->code, $entry['hash'])) {
            $entry['attempts']++;
            Cache::put($key, $entry, now()->addMinutes(self::CODE_TTL_MINUTES));

            return back()->withErrors(['code' => __('Invalid code.')]);
        }

        $user->update(['password' => $request->password]);
        Cache::forget($key);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }

    private function cacheKey(int $userId): string
    {
        return "password-reset-code:{$userId}";
    }
}