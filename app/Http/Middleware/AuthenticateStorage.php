<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStorage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            // Si es petición web, redirige al login de Orchid
            if (!$request->expectsJson()) {
                return redirect()->guest(route('platform.login'));
            }
            // API/JSON: responde 401
            return response('Unauthorized.', 401);
        }
        return $next($request);
    }
}
