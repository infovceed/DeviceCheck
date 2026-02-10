
export interface ChartDataPoint {
  label: string;
  Meta?: number;
  Arrival?: number;
  Checkout?: number;
}
export type Series = {
  labels: string[];
  name: string;
  values: number[];
};

export type ChartProps = {
  initialSeries?: Series[];
  wsUrl?: string;
  title?: string;
  xLabel?: string;
  yLabel?: string;
  pxPerLabel?: number;
};