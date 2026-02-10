<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateWebSocketKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.websocket.api_key', '');
        $provided = (string) $request->header('X-WS-KEY', '');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid or missing websocket key.'
            ], 401);
        }

        return $next($request);
    }
}
