<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    // Lets a user open a page only if their role matches.
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->role() !== $role) {
            abort(403, 'You are not allowed to open this page.');
        }

        return $next($request);
    }
}