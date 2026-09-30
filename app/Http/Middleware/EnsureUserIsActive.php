<?php

namespace App\Http\Middleware;

use App\Enums\IsActiveEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard-aware version. Adjust the "is_active" column name to match yours
 * (I couldn't see your original file).
 */
class EnsureUserIsActive
{
    protected array $loginRoutes = [
        'web' => 'login',
        'patient' => 'patient.login',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->loginRoutes as $guard => $loginRoute) {
            $user = Auth::guard($guard)->user();

         if ($user && $user->is_active !== IsActiveEnum::ACTIVE && $user->is_active !== IsActiveEnum::ACTIVE->value) {
                Auth::guard($guard)->logout();
                $request->session()->regenerateToken();

                return redirect()
                    ->route($loginRoute)
                    ->with('error', 'Your account has been deactivated.');
            }
        }

        return $next($request);
    }
}