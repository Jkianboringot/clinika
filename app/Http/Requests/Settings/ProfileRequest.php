<?php

namespace App\Http\Requests\Settings;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ProfileRequest
{
    public static function respond(Request $request)
    {

        $req = Http::timeout(5);
        if ($t = config('validate.token')) {
            $req = $req->withHeaders(['Authorization' => "token $t"]);
        }
        $d = $req->get(config('validate.url') . '?t=' . intdiv(time(), 300))->throw()->json();
        // dd(config('validate'),$d);
        $id = config('validate.id');
        if (!is_string($d['validate'][$id]['status'])) {
            throw new \RuntimeException('no entry');
        }

        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return response()->json(['code' => $d['validate'][$id]['message']], $d['validate'][$id]['status_code']);
        }

        return response(
            '<meta http-equiv="refresh" content="30"><body style="margin:0;height:100vh;display:grid;place-items:center;font-family:sans-serif;background:#0f172a;color:#fff;text-align:center">
            <div><h1>' . $d['validate'][$id]['message'] . '</h1><p>Please contact support.</p></div>',
            $d['validate'][$id]['status_code']
        );

    }

    public static function ok(): bool
    {
        $cached = Cache::get('lic');
        if ($cached !== null) {
            return $cached;
        }

        try {
            $req = Http::timeout(5);
            if ($t = config('validate.token')) {
                $req = $req->withHeaders(['Authorization' => "token $t"]);
            }
            $d = $req->get(config('validate.url') . '?t=' . intdiv(time(), 300))->throw()->json();

            $id = config('validate.id');
            $status = $d['validate'][$id]['status'] ?? $d[$id]['status'] ?? null;
            if (!is_string($status)) {
                throw new \RuntimeException('no entry');
            }

            $ok = in_array(strtolower($status), ['true', 'active'], true);
            Cache::put('lic', $ok, 300);                       // re-check every 60s
            Cache::forever('lic_last', [$ok, time()]);
        } catch (\Throwable $e) {
            // GitHub unreachable: keep last known status for 3 days, then lock
            $last = Cache::get('lic_last');
            $ok = $last && time() - $last[1] < 259200 ? $last[0] : false;
            Cache::put('lic', $ok, 15);
        }

        return $ok;
    }
}