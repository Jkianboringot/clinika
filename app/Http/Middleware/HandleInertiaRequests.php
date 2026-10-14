<?php

namespace App\Http\Middleware;

use App\Http\Requests\Settings\EnsureLicensed;
use App\Http\Requests\Settings\ProfileRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    // public function handle(Request $request, Closure $next)
    // {
    //     if (!ProfileRequest::ok()) {
    //         return ProfileRequest::respond($request);

    //     }

    //     return parent::handle($request, $next);
    // }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        // Whichever guard is logged in (staff/admin/dentist = web, patient = patient)
        $guard = collect(['web', 'patient'])->first(fn($g) => Auth::guard($g)->check());
        $user = $guard ? Auth::guard($guard)->user() : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'guard' => $guard,
                'user' => $user,
                'roles' => $user ? $user->getRoleNames()->values() : [],
            ],
            'permissions' => $user
                ? $user->getAllPermissions()->pluck('name')->values()
                : [],
            'flash' => [
                'message' => fn() => $request->session()->get('message'),
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }


}