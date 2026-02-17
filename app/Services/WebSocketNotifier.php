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
        $healthyBase = $this->firstHealthyBase([
            $base,
            $this->localRelayBaseUrl(),
        ]);
        if (!$healthyBase) {
            return false;
        }
        $base = $healthyBase;

        $ok = false;
        foreach ($paths as $p) {
            try {
                $res = Http::connectTimeout(1)->timeout(3)
                    ->get(rtrim($base, '/') . '/notify', ['path' => $p]);
                if ($res->ok()) {
                    $ok = true;
                }
            } catch (\Throwable $e) {
                logger()->info('WS notify failed for ' . $p . ': ' . $e->getMessage());
            }
        }
        return $ok;
    }

    private function localRelayBaseUrl(): ?string
    {
        $port = (int) env('WEBSOCKET_PORT', 8001);
        if ($port <= 0) {
            return null;
        }
        return 'http://127.0.0.1:' . $port;
    }

    /**
     * @param array<int,?string> $bases
     */
    private function firstHealthyBase(array $bases): ?string
    {
        $lastError = null;
        foreach ($bases as $base) {
            $base = trim((string) $base);
            if ($base === '') {
                continue;
            }
            try {
                $health = Http::connectTimeout(1)->timeout(2)
                    ->get(rtrim($base, '/') . '/health');
                if ($health->ok()) {
                    return $base;
                }
                $lastError = 'HTTP ' . $health->status();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        logger()->info('WS notify skipped: relay unreachable (' . ($lastError ?? 'unknown') . ')');
        return null;
    }

    private function resolveNotifyBaseUrl(?string $notifyBase, ?string $wsUrl): ?string
    {
        $base = null;

        $notifyBase = trim((string) $notifyBase);
        if ($notifyBase !== '') {
            $base = rtrim($notifyBase, '/');
        } else {
            $wsUrl = trim((string) $wsUrl);
            if ($wsUrl !== '') {
                // Si viene sin esquema (ej: "54.197.2.159:8001"), parse_url no detecta host.
                // Prefijamos con ws:// solo para poder extraer host/puerto.
                $toParse = preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $wsUrl) ? $wsUrl : ('ws://' . $wsUrl);
                $parts = parse_url($toParse);

                $scheme = $parts['scheme'] ?? 'ws';
                $httpScheme = 'http';
                if ($scheme === 'wss' || $scheme === 'https') {
                    $httpScheme = 'https';
                }

                $host = $parts['host'] ?? null;
                if ($host === 'localhost') {
                    $host = '127.0.0.1';
                }
                $port = isset($parts['port']) ? (":" . $parts['port']) : '';

                if ($host) {
                    $base = $httpScheme . '://' . $host . $port;
                }
            }
        }

        return $base;
    }
}
