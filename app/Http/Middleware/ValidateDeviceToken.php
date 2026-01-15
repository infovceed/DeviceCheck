<?php

namespace App\Http\Middleware;

use App\Models\DeviceToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDeviceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Falta el token del dispositivo.'
            ], 401);
        }

        $record = DeviceToken::where('token', $token)->first();

        if (!$record || !$record->isValid()) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Token inválido o expirado.'
            ], 401);
        }

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $auth = $request->header('Authorization');
        if ($auth && preg_match('/^Bearer\s+(.*)$/i', $auth, $m)) {
            return trim($m[1]);
        }

        $headerToken = $request->header('X-Device-Token');
        if ($headerToken) {
            return trim($headerToken);
        }

        return null;
    }
}
