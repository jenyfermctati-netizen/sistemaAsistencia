<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleDeviceSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $request->hasSession() || ! Schema::hasColumn('users', 'active_session_id')) {
            return $next($request);
        }

        $sessionId = $request->session()->getId();
        $activeSessionId = DB::table('users')
            ->where('id', $user->getKey())
            ->value('active_session_id');

        if ($activeSessionId === null) {
            DB::table('users')
                ->where('id', $user->getKey())
                ->whereNull('active_session_id')
                ->update(['active_session_id' => $sessionId]);

            $activeSessionId = DB::table('users')
                ->where('id', $user->getKey())
                ->value('active_session_id');
        }

        if (! is_string($activeSessionId) || ! hash_equals($activeSessionId, $sessionId)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Esta cuenta inició sesión en otro dispositivo. Vuelve a ingresar para continuar.',
            ]);
        }

        return $next($request);
    }
}
