<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $allowedRoles = collect($roles)
            ->flatMap(fn ($role) => preg_split('/[|,]/', (string) $role) ?: [])
            ->map(fn ($role) => UserRole::tryFrom(strtolower(trim((string) $role))))
            ->filter()
            ->values();

        if (! Auth::check() || $allowedRoles->isEmpty()) {
            abort(403);
        }

        $rawRole = strtolower(trim((string) Auth::user()?->getRawOriginal('role')));
        $currentRole = Auth::user()?->role instanceof UserRole
            ? Auth::user()->role
            : UserRole::tryFrom($rawRole);

        if (! $currentRole || ! $allowedRoles->contains(fn (UserRole $allowedRole) => $allowedRole === $currentRole)) {
            abort(403);
        }

        return $next($request);
    }
}
