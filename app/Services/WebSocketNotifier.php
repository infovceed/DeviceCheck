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
        $wsUrl = trim((string) config('services.websocket.url', ''));
        if ($wsUrl === '') {
            $wsUrl = trim((string) env('WEBSOCKET_URL', ''));
        }

        $notifyBase = trim((string) env('WEBSOCKET_NOTIFY_URL', ''));
        if ($notifyBase === '') {
            $notifyBase = null;
        }

        $base = $this->resolveNotifyBaseUrl($notifyBase, $wsUrl);
        if (!$base) {
            logger()->info('WS notify skipped: no base URL', [
                'notify_base' => $notifyBase,
                'ws_url' => $wsUrl,
            ]);
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
        if ($notifyBase) {
            return rtrim($notifyBase);
        }

        $wsUrl = trim((string) $wsUrl);
        if ($wsUrl === '') {
            return null;
        }

        // Si viene sin esquema (ej: "54.197.2.159:8001"), parse_url no detecta host.
        // Prefijamos con ws:// solo para poder extraer host/puerto.
        $toParse = preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $wsUrl) ? $wsUrl : ('ws://' . $wsUrl);

        $parts = parse_url($toParse);
        $scheme = $parts['scheme'] ?? 'ws';
        $httpScheme = $scheme === 'wss' ? 'https' : ($scheme === 'https' ? 'https' : 'http');
        $host = $parts['host'] ?? null;
        if ($host === 'localhost') {$host = '127.0.0.1';}
        $port = isset($parts['port']) ? (":" . $parts['port']) : '';
        if (!$host) {return null;}
        return $httpScheme . '://' . $host . $port;
    }
}
