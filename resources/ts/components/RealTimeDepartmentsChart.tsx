import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ResponsiveContainer,
  BarChart,
  CartesianGrid,
  XAxis,
  YAxis,
  Tooltip,
  Legend,
  Bar,
  LabelList,
} from 'recharts';
import type { ChartDataPoint, Series,ChartProps } from '../interfaces/chart';
import { departmentsBus } from '../services/departmentsBus';

function toChartData(series: Series[] | undefined): Array<ChartDataPoint> {
  if (!series || series.length === 0) return [];
  const baseLabels = series[0].labels ?? [];

  return baseLabels.map((label, idx) => {
    const row: ChartDataPoint  = { label };
    series.forEach((s, sIdx) => {
      const value = s.values?.[idx] ?? 0;
      const name = s.name ?? '';
      if (/^meta$/i.test(name)) row.Meta = value;
      else if (/arrival|llegada/i.test(name)) row.Arrival = value;
      else if (/check[- ]?out|checkout|salida/i.test(name)) row.Checkout = value;
      else {
        if (sIdx === 0) row.Meta = value;
        else if (sIdx === 1) row.Arrival = value;
        else if (sIdx === 2) row.Checkout = value;
        else (row as any)[name || `Serie ${sIdx + 1}`] = value;
      }
    });
    return row;
  });
}

export default function RealTimeDepartmentsChart({ initialSeries, wsUrl, title = 'Reporte por departamento', xLabel = 'Departamento', yLabel = 'Dispositivos reportados', pxPerLabel = 150 }: ChartProps) {
  const [series, setSeries] = useState<Series[] | undefined>(initialSeries);
  const data = useMemo(() => toChartData(series), [series]);
  const contentWidth = useMemo(() => {
    const n = data.length;
    const min = 700;
    return Math.max(min, n * pxPerLabel);
  }, [data, pxPerLabel]);

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
    let intervalId: any = null;
    if (typeof ResizeObserver !== 'undefined') {
      ro = new ResizeObserver(check);
      ro.observe(el);
    } else {
      intervalId = setInterval(check, 250);
    }
    return () => {
      try { ro?.disconnect(); } catch {}
      if (intervalId) clearInterval(intervalId);
    };
  }, []);


  function resolveWsUrl(input?: string): string {
    const scheme = location.protocol === 'https:' ? 'wss' : 'ws';
    const defaultUrl = `${scheme}://${location.host}/ws/departments`;

    if (!input) return defaultUrl;

    const trimmed = input.trim();
    // Si ya viene en esquema ws/wss, usamos tal cual añadiendo la ruta si falta
    if (/^wss?:\/\//i.test(trimmed)) {
      return /\/ws\/departments(\/?$)/.test(trimmed)
        ? trimmed
        : `${trimmed.replace(/\/$/, '')}/ws/departments`;
    }

    // Si viene en http/https, convertimos a ws/wss y añadimos la ruta
    if (/^https?:\/\//i.test(trimmed)) {
      const url = new URL(trimmed);
      const wsBase = `${url.protocol === 'https:' ? 'wss' : 'ws'}://${url.host}${url.pathname}`.replace(/\/$/, '');
      return /\/ws\/departments(\/?$)/.test(wsBase) ? wsBase : `${wsBase}/ws/departments`;
    }

    // Ruta relativa o host sin esquema: construimos con el host actual
    const base = `${scheme}://${location.host}/${trimmed}`.replace(/\/$/, '');
    return /\/ws\/departments(\/?$)/.test(base) ? base : `${base}/ws/departments`;
  }

  function appendFiltersToWsUrl(baseUrl: string): string {
    // Pasar filtros actuales como query params al servidor WS
    const current = new URLSearchParams(location.search);
    const params = new URLSearchParams();
    const keys = ['department', 'municipality', 'position'];
    keys.forEach((key) => {
      const plain = current.getAll(key);
      const bracket = current.getAll(`${key}[]`);
      const values = [...plain, ...bracket];
      values.forEach((v) => params.append(`${key}[]`, v));
    });
    // chart_date es singular
    const date = current.get('chart_date');
    if (date) params.append('chart_date', date);
    if ([...params.entries()].length === 0) return baseUrl;

    const url = new URL(baseUrl, location.origin);
    // Conservar los query existentes en wsUrl, añadir filtros
    const merged = new URLSearchParams(url.search);
    params.forEach((v, k) => merged.append(k, v));
    url.search = merged.toString();
    return url.toString();
  }

  useEffect(() => {
    let url = resolveWsUrl(wsUrl);
    url = appendFiltersToWsUrl(url);
    const unsubscribe = departmentsBus.subscribe(url, (nextSeries) => {
      if (Array.isArray(nextSeries)) setSeries(nextSeries);
    });
    return () => unsubscribe();
  }, [wsUrl]);

  return (
    <div className="card bg-white rounded shadow-sm mb-3">
      <div className="card-body">
        <h6 className="card-title mb-3">{title}</h6>
        <div ref={containerRef} style={{ width: '100%', height: 400, overflowX: 'auto' }}>
          <div style={{ width: contentWidth, height: '100%' }}>
            {ready ? (
              <ResponsiveContainer width="100%" height="100%" minWidth={100} minHeight={100}>
                <BarChart data={data} margin={{ top: 20, right: 30, left: 10, bottom: 60 }}>
              <CartesianGrid strokeDasharray="3 3" />
                <XAxis
                  dataKey="label"
                  interval={0}
                  height={60}
                  tickMargin={10}
                  label={{ value: xLabel, position: 'insideLeft', offset: 0, dy: 20 }}
                />
              <YAxis allowDecimals={false} label={{ value: yLabel, angle: -90, position: 'insideLeft', dy: 50 }} />
              <Tooltip />
              <Legend
                layout="horizontal"
                verticalAlign="bottom"
                align="left"
                formatter={(value: any) => (
                  <span style={{ marginRight: 12 }}>{String(value)}</span>
                )}
              />
              <Bar dataKey="Meta" name="Meta" fill="#92D050" radius={[10, 10, 0, 0]}>
                <LabelList dataKey="Meta" position="top" />
              </Bar>
              <Bar dataKey="Arrival" name="Llegada" fill="#002060" radius={[10, 10, 0, 0]}>
                <LabelList dataKey="Arrival" position="top" />
              </Bar>
              <Bar dataKey="Checkout" name="Salida" fill="#FF8805" radius={[10, 10, 0, 0]}>
                <LabelList dataKey="Checkout" position="top" />
              </Bar>
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div className="d-flex align-items-center justify-content-center" style={{ width: '100%', height: '100%' }}>
                <span className="text-muted">Cargando gráfico…</span>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
