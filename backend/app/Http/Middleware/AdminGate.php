<?php

namespace App\Http\Middleware;

use App\Services\UserSchemaResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminGate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = UserSchemaResolver::resolveFromUserModel($request->user('api'));
        if ($user->role !== 'admin') {
            throw new AccessDeniedHttpException('Forbidden');
        }

        return $next($request);
    }
}
