<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi diperlukan.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "Akses ditolak: Anda tidak memiliki izin untuk tindakan ini ({$permission}).",
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
