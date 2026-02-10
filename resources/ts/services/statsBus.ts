type Subscriber = (stats: Record<string, any>) => void;

class StatsBus {
  private connections: Map<string, { ws: WebSocket | null; subs: Set<Subscriber> }>; 

  constructor() {
    this.connections = new Map();
  }

  private ensure(url: string) {
    let entry = this.connections.get(url);
    if (!entry) {
      entry = { ws: null, subs: new Set() };
      this.connections.set(url, entry);
    }
    if (!entry.ws || entry.ws.readyState > 1) {
      try {
        entry.ws = new WebSocket(url);
        entry.ws.onmessage = (ev) => {
          try {
            const msg = JSON.parse(ev.data);
            const stats = msg?.stats ?? msg;
            entry!.subs.forEach((fn) => {
              try { fn(stats); } catch {}
            });
          } catch {}
        };
        entry.ws.onclose = () => {
          // Intento de reconexión básico
          setTimeout(() => {
            const current = this.connections.get(url);
            if (current && current.subs.size > 0) this.ensure(url);
          }, 2000);
        };
      } catch {}
    }
    return entry;
  }

  subscribe(url: string, fn: Subscriber): () => void {
    const entry = this.ensure(url);
    entry.subs.add(fn);
    return () => {
      const e = this.connections.get(url);
      if (!e) return;
      e.subs.delete(fn);
      if (e.subs.size === 0) {
        try { e.ws?.close(); } catch {}
        this.connections.delete(url);
      }
    };
  }
}

export const statsBus = new StatsBus();
