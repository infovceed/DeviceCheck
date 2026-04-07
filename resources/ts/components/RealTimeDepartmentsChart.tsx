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
  Rectangle,
  LabelList,
} from 'recharts';
import type { ChartDataPoint, Series,ChartProps,Radius,ConditionalBarShapeProps } from '../interfaces/chart';
import { departmentsBus } from '../services/departmentsBus';

type CustomTooltipEntry = {
  name?: string | number;
  value?: string | number;
  color?: string;
};

type CustomTooltipProps = {
  active?: boolean;
  payload?: CustomTooltipEntry[];
  label?: string | number;
};

function getLegendTextColor(label: string): string {
  return /salida/i.test(label) ? '#FF8805' : '#002060';
}

function renderTooltipContent({ active, payload, label }: CustomTooltipProps): React.ReactNode {
  if (!active || !payload || payload.length === 0) return null;

  return (
    <div style={{ backgroundColor: '#FFFFFF', border: '1px solid #E5E7EB', padding: '8px 10px' }}>
      <div style={{ color: '#111827', marginBottom: 6 }}>{String(label)}</div>
      {payload.map((entry, index) => {
        const seriesLabel = String(entry.name ?? '');
        const textColor = getLegendTextColor(seriesLabel);
        const markerColor = entry.color ?? textColor;
        const value = String(entry.value ?? 0);

        return (
          <div
            key={`${seriesLabel}-${index}`}
            style={{ display: 'flex', alignItems: 'center', gap: 8, color: textColor, marginTop: 3 }}
          >
            <span
              style={{
                width: 10,
                height: 10,
                borderRadius: 2,
                backgroundColor: markerColor,
                display: 'inline-block',
                flexShrink: 0,
              }}
            />
            <span>{`${seriesLabel} : ${value}`}</span>
          </div>
        );
      })}
    </div>
  );
}

function ConditionalRoundedBarShape({ dataKey, payload, ...props }: ConditionalBarShapeProps) {
  const currentValue = payload?.[dataKey] ?? 0;
  const metaValue = payload?.Meta ?? 0;
  const isPendingBar = dataKey === 'PArrival' || dataKey === 'PCheckout';
  const shouldRound = isPendingBar ? currentValue > 0 : currentValue === metaValue;
  const radius: Radius | 0 = shouldRound ? [10, 10, 0, 0] : 0;

  return <Rectangle {...props} radius={radius} />;
}

function toChartData(series: Series[] | undefined): Array<ChartDataPoint> {
  if (!series || series.length === 0) return [];
  const baseLabels = series[0].labels ?? [];

  return baseLabels.map((label, idx) => {
    const row: ChartDataPoint  = { label };
    series.forEach((s, sIdx) => {
      const value = s.values?.[idx] ?? 0;
      const name = (s.name ?? '').trim();
      if (/^meta$/i.test(name)) row.Meta = value;
      else if (/^(?:p\s*arrival|pendientes?\s*llegada|pending\s*arrival)$/i.test(name)) row.PArrival = value;
      else if (/arrival|llegada/i.test(name)) row.Arrival = value;
      else if (/^(?:p\s*checkout|pendientes?\s*salida|pending\s*checkout|psalida)$/i.test(name)) row.PCheckout = value;
      else if (/check[- ]?out|checkout|salida/i.test(name)) row.Checkout = value;
      else {
        if (sIdx === 0) row.Meta = value;
        else if (sIdx === 1) row.Arrival = value;
        else if (sIdx === 2) row.Checkout = value;
        else if (sIdx === 3) row.PArrival = value;
        else if (sIdx === 4) row.PCheckout = value;
        else (row as any)[name || `Serie ${sIdx + 1}`] = value;
      }
    });

    const meta = Math.max(0, Number(row.Meta ?? 0));
    const arrivalReported = Math.max(0, Number(row.Arrival ?? 0));
    const checkoutReported = Math.max(0, Number(row.Checkout ?? 0));

    // Garantiza consistencia visual: pendientes + reportados = meta.
    const normalizedArrival = Math.min(arrivalReported, meta);
    const normalizedCheckout = Math.min(checkoutReported, meta);

    row.Meta = meta;
    row.Arrival = normalizedArrival;
    row.Checkout = normalizedCheckout;
    row.PArrival = Math.max(0, meta - normalizedArrival);
    row.PCheckout = Math.max(0, meta - normalizedCheckout);

    const pendingArrival = row.PArrival;
    const pendingCheckout = row.PCheckout;

    // Reglas de visibilidad solicitadas por negocio para etiquetas.
    row.ArrivalCenterLabel = normalizedArrival > 0 && normalizedArrival < meta ? String(normalizedArrival) : '';
    row.PArrivalCenterLabel = pendingArrival > 0 && pendingArrival < meta ? String(pendingArrival) : '';
    row.CheckoutCenterLabel = normalizedCheckout > 0 && normalizedCheckout < meta ? String(normalizedCheckout) : '';
    row.PCheckoutCenterLabel = pendingCheckout > 0 && pendingCheckout < meta ? String(pendingCheckout) : '';
    row.MetaArrivalTopLabel = meta > 0 ? String(meta) : '';
    row.MetaCheckoutTopLabel = meta > 0 ? String(meta) : '';

    return row;
  });
}

function normalizeFilterValues(rawValues: string[]): string[] {
  const uniqueValues = new Set<string>();

  rawValues.forEach((value) => {
    value.split(',').forEach((part) => {
      const cleaned = part.trim();
      if (cleaned.length > 0) uniqueValues.add(cleaned);
    });
  });

  return Array.from(uniqueValues);
}
function legendTextFormatter(value: string | number): React.ReactNode {
  const label = String(value);
  const color = getLegendTextColor(label);

  return <span style={{ marginRight: 12, color }}>{label}</span>;
}
export default function RealTimeDepartmentsChart({ initialSeries, wsUrl, title = 'Reporte por departamento', xLabel = 'Departamento', yLabel = 'Dispositivos reportados', pxPerLabel = 150 }: ChartProps) {
  const [series, setSeries] = useState<Series[] | undefined>(initialSeries);
  const [locationSearch, setLocationSearch] = useState<string>(() => location.search);

  useEffect(() => {
    setSeries(initialSeries);
  }, [initialSeries]);

  useEffect(() => {
    const syncSearch = () => {
      const next = location.search;
      setLocationSearch((prev) => (prev === next ? prev : next));
    };

    syncSearch();
    document.addEventListener('turbo:load', syncSearch);
    document.addEventListener('turbo:render', syncSearch);
    globalThis.addEventListener('popstate', syncSearch);

    const watchId = globalThis.setInterval(syncSearch, 400);

    return () => {
      document.removeEventListener('turbo:load', syncSearch);
      document.removeEventListener('turbo:render', syncSearch);
      globalThis.removeEventListener('popstate', syncSearch);
      globalThis.clearInterval(watchId);
    };
  }, []);

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

  function appendFiltersToWsUrl(baseUrl: string, search: string): string {
    // Pasar filtros actuales como query params al servidor WS
    const current = new URLSearchParams(search);
    const params = new URLSearchParams();
    const keys = ['department', 'municipality', 'position'];

    keys.forEach((key) => {
      const plain = current.getAll(key);
      const bracket = current.getAll(`${key}[]`);
      const values = normalizeFilterValues([...plain, ...bracket]);
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

  const wsUrlWithFilters = useMemo(() => {
    let url = resolveWsUrl(wsUrl);
    url = appendFiltersToWsUrl(url, locationSearch);
    return url;
  }, [wsUrl, locationSearch]);

  useEffect(() => {
    const unsubscribe = departmentsBus.subscribe(wsUrlWithFilters, (nextSeries) => {
      if (Array.isArray(nextSeries)) setSeries(nextSeries);
    });
    return () => unsubscribe();
  }, [wsUrlWithFilters]);

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
                  <Tooltip content={renderTooltipContent} />
                  <Legend
                    layout="horizontal"
                    verticalAlign="bottom"
                    align="left"
                    formatter={legendTextFormatter}
                  />
                  <Bar
                    dataKey="Arrival"
                    stackId="a"
                    name="Reportados Llegada"
                    fill="#002060"
                    shape={<ConditionalRoundedBarShape dataKey="Arrival" />}
                  >
                    <LabelList dataKey="ArrivalCenterLabel" position="center" fill="#FFFFFF" />
                  </Bar>
                  <Bar
                    dataKey="PArrival"
                    stackId="a"
                    name="Pendientes Llegada"
                    fill="#00206080"
                    shape={<ConditionalRoundedBarShape dataKey="PArrival" />}
                  >
                    <LabelList dataKey="PArrivalCenterLabel" position="center" fill="#FFFFFF"/>
                    <LabelList dataKey="MetaArrivalTopLabel" position="top" />
                  </Bar>

                  <Bar
                    dataKey="Checkout"
                    stackId="b"
                    name="Reportados Salida"
                    fill="#FF8805"
                    shape={<ConditionalRoundedBarShape dataKey="Checkout" />}
                  >
                    <LabelList dataKey="CheckoutCenterLabel" position="center" fill="#FFFFFF" />
                  </Bar>
                  <Bar
                    dataKey="PCheckout"
                    stackId="b"
                    name="Pendientes Salida"
                    fill="#FF880580"
                    shape={<ConditionalRoundedBarShape dataKey="PCheckout" />}
                  >
                    <LabelList dataKey="PCheckoutCenterLabel" position="center" fill="#FFFFFF"/>
                    <LabelList dataKey="MetaCheckoutTopLabel" position="top" />
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
