<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceAuthController extends Controller
{
    public function issueToken(Request $request)
    {
        $providedKey = $request->header('X-API-KEY');
        $expectedKey = config('services.device.api_key');

        if (!$expectedKey || !$providedKey || !hash_equals((string)$expectedKey, (string)$providedKey)) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'API key inválida o no configurada.'
            ], 401);
        }

        $ttl = (int)($request->input('ttl_hours', 24));
        if ($ttl <= 0) {
            $ttl = 24;
        }

        $deviceToken = DeviceToken::issue($ttl);

        return response()->json([
            'status' => 'ok',
            'token' => $deviceToken->token,
            'expires_at' => optional($deviceToken->expires_at)->toIso8601String(),
            'token_type' => 'Bearer',
        ], 201);
    }
}
