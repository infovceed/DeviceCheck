import type { Series } from '../interfaces/chart';

type Subscriber = (series: Series[] | undefined) => void;

class DepartmentsBus {
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
            const nextSeries: Series[] | undefined = msg?.payload ?? msg?.series ?? msg;
            entry!.subs.forEach((fn) => {
              try { fn(nextSeries); } catch {}
            });
          } catch {}
        };
        entry.ws.onclose = () => {
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

export const departmentsBus = new DepartmentsBus();
