<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Support role:admin,staff and role:admin|staff
        $allowedRoles = [];
        foreach ($roles as $roleGroup) {
            foreach (preg_split('/[,|]/', $roleGroup) as $singleRole) {
                $allowedRoles[] = trim($singleRole);
            }
        }

        if (! empty($allowedRoles) && ! in_array($user->role, $allowedRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki hak akses untuk resource ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
