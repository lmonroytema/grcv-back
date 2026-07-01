<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return new JsonResponse([
                'message' => 'No autenticado.',
            ], 401);
        }

        $roleName = $user->role?->name;

        if ($roleName === null || !in_array($roleName, $roles, true)) {
            return new JsonResponse([
                'message' => 'No tienes permisos para esta accion.',
            ], 403);
        }

        return $next($request);
    }
}
