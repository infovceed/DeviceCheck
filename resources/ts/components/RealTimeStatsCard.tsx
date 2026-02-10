import React, { useEffect, useMemo, useState } from 'react';
import StatsCard from './StatsCard';
import type { RealTimeStatsCardProps, StatsMetricKey } from '../interfaces/cards';
import { statsBus } from '../services/statsBus';

function resolveWsUrl(input?: string | null): string {
  const scheme = location.protocol === 'https:' ? 'wss' : 'ws';
  const defaultUrl = `${scheme}://${location.host}/ws/stats`;
  if (!input) return defaultUrl;
  const trimmed = input.trim();
  if (/^wss?:\/\//i.test(trimmed)) {
    return /\/ws\/(stats)(\/?$)/.test(trimmed) ? trimmed : `${trimmed.replace(/\/$/, '')}/ws/stats`;
  }
  if (/^https?:\/\//i.test(trimmed)) {
    const url = new URL(trimmed);
    const wsBase = `${url.protocol === 'https:' ? 'wss' : 'ws'}://${url.host}${url.pathname}`.replace(/\/$/, '');
    return /\/ws\/(stats)(\/?$)/.test(wsBase) ? wsBase : `${wsBase}/ws/stats`;
  }
  const base = `${scheme}://${location.host}/${trimmed}`.replace(/\/$/, '');
  return /\/ws\/(stats)(\/?$)/.test(base) ? base : `${base}/ws/stats`;
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

export default function RealTimeStatsCard({ metric, value, subtitle, icon, iconColor, wsUrl }: RealTimeStatsCardProps) {
  const [current, setCurrent] = useState<number | string | undefined>(value);
  const url = useMemo(() => appendFiltersToWsUrl(resolveWsUrl(wsUrl ?? undefined)), [wsUrl]);

  useEffect(() => {
    const unsubscribe = statsBus.subscribe(url, (stats) => {
      const next = stats?.[metric as StatsMetricKey];
      if (typeof next !== 'undefined') setCurrent(next);
    });
    return () => unsubscribe();
  }, [url, metric]);

  const title = useMemo(() => {
    if (metric.includes('percentage') && typeof current === 'number') return `${current}%`;
    return current as any;
  }, [current, metric]);

  return <StatsCard title={title as any} subtitle={subtitle} icon={icon} iconColor={iconColor} />;
}
