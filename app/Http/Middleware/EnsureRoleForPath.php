<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleForPath
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasRole($role)) {
            // Redirect them to their dashboard instead of 403
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('provider')) {
                return redirect()->route('provider.dashboard');
            }

            return redirect()->route('home');
        }

        return $next($request);
    }
}
