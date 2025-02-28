<?php

namespace App\Http\Middleware;

use App\Services\UserSchemaResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Gatekeeper
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $allowed): Response
    {
        $user = UserSchemaResolver::resolveFromUserModel($request->user('api'));
        $allowedArr = explode('|', $allowed);

        if (!in_array($user->role, $allowedArr)) {
            throw new AccessDeniedHttpException('Forbidden');
        }

        return $next($request);
    }
}
