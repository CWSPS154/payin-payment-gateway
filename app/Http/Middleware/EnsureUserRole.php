<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect($role === User::ROLE_ADMIN ? '/admin/login' : '/merchant/login');
        }

        abort_unless($user->role === $role, 403);

        return $next($request);
    }
}
