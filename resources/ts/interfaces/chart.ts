
export interface ChartDataPoint {
  label: string;
  Meta?: number;
  Arrival?: number;
  PArrival?: number;
  Checkout?: number;
  PCheckout?: number;
  ArrivalCenterLabel?: string;
  PArrivalCenterLabel?: string;
  CheckoutCenterLabel?: string;
  PCheckoutCenterLabel?: string;
  MetaArrivalTopLabel?: string;
  MetaCheckoutTopLabel?: string;
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

export type Radius = [number, number, number, number];

export type ConditionalBarShapeProps = Readonly<{
  dataKey: 'Arrival' | 'PArrival' | 'Checkout' | 'PCheckout';
  payload?: ChartDataPoint;
  x?: number;
  y?: number;
  width?: number;
  height?: number;
  fill?: string;
}>;