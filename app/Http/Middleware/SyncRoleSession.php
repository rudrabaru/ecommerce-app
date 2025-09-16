<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncRoleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $roles = $request->user()->getRoleNames()->toArray();
            session(['rbac.roles' => $roles]);
        }

        return $next($request);
    }
}


