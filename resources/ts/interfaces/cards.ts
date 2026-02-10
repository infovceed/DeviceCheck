export interface UserCardProps {
  name?: string;
  email?: string;
}

export interface CounterCardProps {
  count: number;
  onIncrement: () => void;
}
export interface StatsCardProps {
  title?: string;
  subtitle?: string;
  icon?: string;
  iconColor?: string;
}

export type StatsMetricKey =
  | 'totalRecords'
  | 'totalReported'
  | 'totalReportedIn'
  | 'totalReportedOut'
  | 'percentage'
  | 'percentageIn'
  | 'percentageOut';

export interface RealTimeStatsCardProps {
  metric: StatsMetricKey;
  value?: number | string;
  subtitle?: string;
  icon?: string;
  iconColor?: string;
  wsUrl?: string | null;
}