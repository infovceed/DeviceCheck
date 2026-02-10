import '../js/bootstrap';
import React, { useState } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import CounterCard from './components/CounterCard';
import StatsCard from './components/StatsCard';
import RealTimeDepartmentsChart from './components/RealTimeDepartmentsChart';
import RealTimeMunicipalitiesChart from './components/RealTimeMunicipalitiesChart';
import type { RealTimeDepartmentsMountProps } from './interfaces/realtime';
import type { StatsCardProps, RealTimeStatsCardProps } from './interfaces/cards';
import RealTimeStatsCard from './components/RealTimeStatsCard';
import RealTimePie from './components/RealTimePie';

declare global {
  // Cache global de roots para evitar doble montaje
  // y permitir re-render seguro si este entry se evalúa más de una vez.
  // eslint-disable-next-line no-var
  var __REACT_ROOTS__: WeakMap<Element, Root> | undefined;
}

function mount(el: Element, node: React.ReactNode) {
  const roots = (globalThis.__REACT_ROOTS__ ??= new WeakMap<Element, Root>());
  const root = roots.get(el) ?? createRoot(el);
  roots.set(el, root);
  root.render(<React.StrictMode>{node}</React.StrictMode>);
}

function mountById(id: string, node: React.ReactNode) {
  const el = document.getElementById(id);
  if (!el) return;
  mount(el, node);
}

function parseJsonDataset<T>(raw: string | undefined, fallback: T): T {
  if (!raw) return fallback;
  try {
    return JSON.parse(raw) as T;
  } catch (err: unknown) {
    console.error('Error parsing dataset JSON:', err);
    return fallback;
  }
}

function App() {
  const [count, setCount] = useState(0);
  return <CounterCard count={count} onIncrement={() => setCount((c) => c + 1)} />;
}

const targets = ['root', 'orchid-react-root'];
function mountAll() {
  const targets = ['root', 'orchid-react-root'];
  targets.forEach((id) => mountById(id, <App />));

  // Montar tarjeta única por id si existe
  const userCardEl = document.getElementById('stats-card');
  if (userCardEl) {
    const props = parseJsonDataset<StatsCardProps>(userCardEl.dataset.props, {});
    mount(userCardEl, <StatsCard {...props} />);
  }

  // Montar todas las tarjetas de stats presentes en la vista
  const statsCardEls = document.querySelectorAll('.stats-card');
  statsCardEls.forEach((el) => {
    const props = parseJsonDataset<StatsCardProps>((el as HTMLElement).dataset.props, {});
    mount(el, <StatsCard {...props} />);
  });

  // Montar tarjetas de stats en tiempo real
  const rtStatsEls = document.querySelectorAll('.realtime-stats-card');
  rtStatsEls.forEach((el) => {
    const props = parseJsonDataset<RealTimeStatsCardProps>((el as HTMLElement).dataset.props, {} as any);
    mount(el, <RealTimeStatsCard {...props} />);
  });

  // Montaje del chart de departamentos en tiempo real (Recharts + WebSocket)
  const depRealtimeEl = document.getElementById('orchid-departments-realtime');
  if (depRealtimeEl) {
    const props = parseJsonDataset<RealTimeDepartmentsMountProps>(depRealtimeEl.dataset.props, {});
    mount(
      depRealtimeEl,
      <RealTimeDepartmentsChart
        initialSeries={props.initialSeries}
        wsUrl={props.wsUrl}
        title={props.title}
        pxPerLabel={props.pxPerLabel}
      />,
    );
  }

  // Montaje del chart de municipios en tiempo real
  const munRealtimeEl = document.getElementById('orchid-municipalities-realtime');
  if (munRealtimeEl) {
    const props = parseJsonDataset<any>(munRealtimeEl.dataset.props, {});
    mount(
      munRealtimeEl,
      <RealTimeMunicipalitiesChart
        initialSeries={props.initialSeries}
        wsUrl={props.wsUrl}
        title={props.title}
        pxPerLabel={props.pxPerLabel}
      />,
    );
  }

  // Montar pie charts en tiempo real
  const pieEls = document.querySelectorAll('.realtime-pie');
  pieEls.forEach((el) => {
    const props = parseJsonDataset<any>((el as HTMLElement).dataset.props, {});
    mount(
      el,
      <RealTimePie mode={props.mode} title={props.title} wsUrl={props.wsUrl} initial={props.initial} />,
    );
  });
}
// Ejecutar en carga inicial y tras navegaciones Turbo (Orchid)
queueMicrotask(() => mountAll());
document.addEventListener('turbo:load', mountAll);
document.addEventListener('turbo:render', mountAll);


