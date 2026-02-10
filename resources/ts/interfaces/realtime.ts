import type { Series } from '../components/RealTimeDepartmentsChart';

export type RealTimeDepartmentsMountProps = {
  initialSeries?: Series[];
  wsUrl?: string;
  title?: string;
  pxPerLabel?: number;
};
