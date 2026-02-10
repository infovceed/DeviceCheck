import React, { useEffect, useMemo, useRef, useState } from 'react';
import { ResponsiveContainer, PieChart, Pie, Cell, Tooltip, Legend } from 'recharts';
import { statsBus } from '../services/statsBus';

type Mode = 'arrival' | 'departure';

interface RealTimePieProps {
  mode: Mode;
  title?: string;
  wsUrl?: string | null;
  initial?: { pending: number; reported: number };
}

interface PieDatum { name: string; value: number }

function resolveWsUrl(input?: string | null): string {
  const scheme = location.protocol === 'https:' ? 'wss' : 'ws';
  const defaultUrl = `${scheme}://${location.host}/ws/stats`;
  if (!input) return defaultUrl;
  const trimmed = input.trim();
  if (/^wss?:\/\//i.test(trimmed)) {
    return /\/ws\/stats(\/?$)/.test(trimmed) ? trimmed : `${trimmed.replace(/\/$/, '')}/ws/stats`;
  }
  if (/^https?:\/\//i.test(trimmed)) {
    const url = new URL(trimmed);
    const wsBase = `${url.protocol === 'https:' ? 'wss' : 'ws'}://${url.host}${url.pathname}`.replace(/\/$/, '');
    return /\/ws\/stats(\/?$)/.test(wsBase) ? wsBase : `${wsBase}/ws/stats`;
  }
  const base = `${scheme}://${location.host}/${trimmed}`.replace(/\/$/, '');
  return /\/ws\/stats(\/?$)/.test(base) ? base : `${base}/ws/stats`;
}

function appendFiltersToWsUrl(baseUrl: string): string {
  const current = new URLSearchParams(location.search);
  const params = new URLSearchParams();
  const keys = ['department', 'municipality', 'position'];
  keys.forEach((key) => {
    const plain = current.getAll(key);
    const bracket = current.getAll(`${key}[]`);
    const values = [...plain, ...bracket];
    values.forEach((v) => params.append(`${key}[]`, v));
  });
  const date = current.get('chart_date');
  if (date) params.append('chart_date', date);
  if ([...params.entries()].length === 0) return baseUrl;
  const url = new URL(baseUrl, location.origin);
  const merged = new URLSearchParams(url.search);
  params.forEach((v, k) => merged.append(k, v));
  url.search = merged.toString();
  return url.toString();
}

export default function RealTimePie({ mode, title, wsUrl, initial }: RealTimePieProps) {
  const [data, setData] = useState<PieDatum[]>([
    { name: 'Pendiente', value: initial?.pending ?? 0 },
    { name: 'Reportado', value: initial?.reported ?? 0 },
  ]);
  const url = useMemo(() => appendFiltersToWsUrl(resolveWsUrl(wsUrl ?? undefined)), [wsUrl]);

  const containerRef = useRef<HTMLDivElement | null>(null);
  const [ready, setReady] = useState(false);
  useEffect(() => {
    const el = containerRef.current;
    if (!el) return;
    const check = () => {
      const w = el.clientWidth;
      const h = el.clientHeight;
      setReady(w > 0 && h > 0);
    };
    check();
    let ro: ResizeObserver | null = null;
    if (typeof ResizeObserver !== 'undefined') {
      ro = new ResizeObserver(check);
      ro.observe(el);
    }
    return () => {
      try { ro?.disconnect(); } catch {}
    };
  }, []);

  useEffect(() => {
    const unsubscribe = statsBus.subscribe(url, (stats) => {
      const total = Number(stats?.totalRecords ?? 0);
      const reported = mode === 'arrival'
        ? Number(stats?.totalReportedIn ?? 0)
        : Number(stats?.totalReportedOut ?? 0);
      const pending = Math.max(0, total - reported);
      setData([
        { name: 'Pendiente', value: pending },
        { name: 'Reportado', value: reported },
      ]);
    });
    return () => unsubscribe();
  }, [url, mode]);

  const COLORS = ['#A6A6A6', mode === 'arrival' ? '#002060' : '#FF8805'];
  const sum = data.reduce((acc, d) => acc + (Number(d.value) || 0), 0);
  const pctPending = sum ? Math.round(((data[0]?.value || 0) / sum) * 1000) / 10 : 0;
  const pctReported = sum ? Math.round(((data[1]?.value || 0) / sum) * 1000) / 10 : 0;

  return (
    <div className="card bg-white mb-3 rounded shadow-sm">
      <div className="card-body">
        <h6 className="card-title mb-2">{title}</h6>
        <div className="d-flex fs-6 mb-3">
          <div className="mr-2 p-2 rounded-2" style={{ backgroundColor: '#A6A6A6', color: '#fff' }}>
            Pendiente: {pctPending}%
          </div>
          <div className="mx-2 p-2 rounded-2" style={{ backgroundColor: mode === 'arrival' ? '#002060' : '#FF8805', color: '#fff' }}>
            Reportado: {pctReported}%
          </div>
        </div>
        <div ref={containerRef} style={{ width: '100%', height: 300 }}>
          {ready ? (
            <ResponsiveContainer width="100%" height="100%" minWidth={100} minHeight={100}>
              <PieChart>
                <Pie data={data} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={100} label>
                  {data.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          ) : (
            <div className="d-flex align-items-center justify-content-center" style={{ width: '100%', height: '100%' }}>
              <span className="text-muted">Cargando gráfico…</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
