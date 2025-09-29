<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ProtectAdminPaths
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = trim($request->path(), '/');

        // Only guard admin-prefixed routes, excluding the admin login endpoints
        if (str_starts_with($path, 'admin')) {
            $isLogin = $path === 'admin/login';
            if ($isLogin) {
                return $next($request);
            }

            if (! Auth::check()) {
                return redirect()->route('admin.login');
            }

            $user = Auth::user();
            if (! ($user->hasRole('admin') || $user->hasRole('provider'))) {
                abort(403, 'Unauthorized');
            }
        }

        return $next($request);
    }
}


