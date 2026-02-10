// Simple WebSocket relay server for departments chart
// Usage: node scripts/ws-server.js

// Cargar variables desde .env cuando se ejecute vía Node
import 'dotenv/config';

import http from 'node:http';
import { WebSocketServer } from 'ws';

const PORT = Number(process.env.WEBSOCKET_PORT || 8001);
const PATHS = {
  departments: '/ws/departments',
  stats: '/ws/stats',
  municipalities: '/ws/municipalities',
};
const LARAVEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
const WEBSOCKET_API_KEY = process.env.WEBSOCKET_API_KEY || '';
const API_ENDPOINTS = {
  [PATHS.departments]: `${LARAVEL_URL.replace(/\/$/, '')}/api/departments/chart`,
  [PATHS.stats]: `${LARAVEL_URL.replace(/\/$/, '')}/api/stats`,
  [PATHS.municipalities]: `${LARAVEL_URL.replace(/\/$/, '')}/api/municipalities/chart`,
};
// Por defecto sin polling; solo push vía /notify
const POLL_MS = Number(process.env.POLL_MS || 0);
// Timeout para requests al backend Laravel durante el push
const FETCH_TIMEOUT_MS = Number(process.env.FETCH_TIMEOUT_MS || 4000);

const server = http.createServer(async (req, res) => {
  // Basic health check endpoint
  if (req.url?.startsWith('/health')) {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', ts: Date.now() }));
    return;
  }

  // Notify endpoint: trigger immediate refresh for connected clients (non-blocking)
  if (req.url?.startsWith('/notify')) {
    try {
      const url = new URL(req.url, `http://${req.headers.host}`);
      const path = url.searchParams.get('path'); // optional filter per WS path

      // Attempt JSON body for POST to specify paths
      let bodyPaths = null;
      if (req.method === 'POST') {
        const chunks = [];
        for await (const chunk of req) chunks.push(chunk);
        const raw = Buffer.concat(chunks).toString('utf8');
        try {
          const json = JSON.parse(raw);
          if (Array.isArray(json?.paths)) bodyPaths = json.paths;
        } catch {}
      }

      // Seleccionar clientes objetivo sin bloquear por su refresco
      const targets = [];
      for (const client of wss.clients) {
        if (client.readyState !== 1) continue;
        const wsPath = client._path;
        if (path && wsPath !== path) continue;
        if (bodyPaths && !bodyPaths.includes(wsPath)) continue;
        if (typeof client._pollAndSend === 'function') targets.push(client);
      }

      // Responder inmediatamente para evitar timeouts del emisor (PHP)
      res.writeHead(202, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ status: 'accepted', targeted: targets.length }));

      // Ejecutar refresco en paralelo, fuera del ciclo de respuesta
      Promise.allSettled(targets.map((c) => c._pollAndSend()))
        .then((results) => {
          const ok = results.filter((r) => r.status === 'fulfilled').length;
          if (ok || results.length) {
            console.log(`[WS] notify processed: ${ok}/${results.length} refreshed`);
          }
        })
        .catch(() => {});
    } catch (err) {
      res.writeHead(500, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ status: 'error', message: String(err?.message || err) }));
    }
    return;
  }

  res.writeHead(404);
  res.end();
});

const wss = new WebSocketServer({ noServer: true });

server.on('upgrade', (req, socket, head) => {
  const { url } = req;
  try {
    const requestUrl = new URL(url, `http://${req.headers.host}`);
    const pathname = requestUrl.pathname;
    if (!Object.values(PATHS).includes(pathname)) {
      socket.write('HTTP/1.1 404 Not Found\r\n\r\n');
      socket.destroy();
      return;
    }
    wss.handleUpgrade(req, socket, head, (ws) => {
      // Pasar params de filtros al handler de conexión y soportar múltiples valores
      const filters = {};
      for (const key of requestUrl.searchParams.keys()) {
        const values = requestUrl.searchParams.getAll(key);
        filters[key] = values.length > 1 ? values : (values[0] ?? '');
      }
      ws._filters = filters;
      ws._path = pathname;
      wss.emit('connection', ws, req);
    });
  } catch (err) {
    console.error('[WS] upgrade error:', err.message || err);
    socket.destroy();
  }
});

wss.on('connection', (ws) => {
  console.log('[WS] client connected');
  const filters = ws._filters || {};
  const path = ws._path || PATHS.departments;
  let intervalId = null;

  async function pollAndSend() {
    try {
      const endpoint = API_ENDPOINTS[path] || API_ENDPOINTS[PATHS.departments];
      const url = new URL(endpoint);
      // Adjuntar filtros del cliente como query params
      Object.entries(filters).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach((item) => url.searchParams.append(k, item));
        else if (typeof v === 'string' && v !== '') url.searchParams.append(k, v);
      });
      // Timeout controlado para evitar colgar el notify
      const controller = new AbortController();
      const tm = setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);
      const headers = WEBSOCKET_API_KEY ? { 'X-WS-KEY': WEBSOCKET_API_KEY } : {};
      const res = await fetch(url, { signal: controller.signal, headers });
      clearTimeout(tm);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();
      const payload = JSON.stringify(json);
      if (ws.readyState === 1) ws.send(payload);
    } catch (err) {
      console.error('[WS] poll error:', err.message || err);
    }
  }
  ws._pollAndSend = pollAndSend;
  if (POLL_MS > 0) {
    intervalId = setInterval(pollAndSend, POLL_MS);
  }

  ws.on('close', () => {
    console.log('[WS] client disconnected');
    if (intervalId) clearInterval(intervalId);
  });
});

server.listen(PORT, () => {
  console.log(`[WS] listening on ws://localhost:${PORT}${PATHS.departments}, ${PATHS.stats}, ${PATHS.municipalities}`);
  if (!WEBSOCKET_API_KEY) {
    console.warn('[WS] WEBSOCKET_API_KEY is empty; Laravel will return 401 for protected API endpoints');
  }
  if (POLL_MS > 0) {
    console.log(`[WS] polling each ${POLL_MS}ms`);
  } else {
    console.log('[WS] polling disabled; use /notify to push updates');
  }
});
