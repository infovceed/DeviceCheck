<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WebSocketNotifier
{
    /**
     * Notify WS relay to refresh clients for given paths.
     * Returns true if at least one notify succeeded.
     */
    public function notify(array $paths = ['/ws/stats']): bool
    {
        $wsUrl = config('services.websocket.url') ?? env('WEBSOCKET_URL');
        $notifyBase = env('WEBSOCKET_NOTIFY_URL');

        $base = $this->resolveNotifyBaseUrl($notifyBase, $wsUrl);
        if (!$base) {
            logger()->info('WS notify skipped: no base URL');
            return false;
        }
        if (!$this->isRelayHealthy($base)) {
            return false;
        }
        $ok = false;
        foreach ($paths as $p) {
            try {
                $res = Http::timeout(2)->get(rtrim($base, '/') . '/notify', ['path' => $p]);
                if ($res->ok()) {
                    $ok = true;
                }
            } catch (\Throwable $e) {
                logger()->info('WS notify failed for ' . $p . ': ' . $e->getMessage());
            }
        }
        return $ok;
    }

    private function isRelayHealthy(string $base): bool
    {
        try {
            $health = Http::timeout(1)->get(rtrim($base, '/') . '/health');
            if (!$health->ok()) {
                logger()->info('WS notify skipped: relay unhealthy');
                return false;
            }
        } catch (\Throwable $e) {
            logger()->info('WS notify skipped: relay unreachable (' . $e->getMessage() . ')');
            return false;
        }
        return true;
    }

    private function resolveNotifyBaseUrl(?string $notifyBase, ?string $wsUrl): ?string
    {
        if ($notifyBase){ return rtrim($notifyBase); }
        if (!$wsUrl) {return null;}
        $parts = parse_url($wsUrl);
        $scheme = $parts['scheme'] ?? 'ws';
        $httpScheme = $scheme === 'wss' ? 'https' : ($scheme === 'https' ? 'https' : 'http');
        $host = $parts['host'] ?? null;
        if ($host === 'localhost') {$host = '127.0.0.1';}
        $port = isset($parts['port']) ? (":" . $parts['port']) : '';
        if (!$host) {return null;}
        return $httpScheme . '://' . $host . $port;
    }
}
