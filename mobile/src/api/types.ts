// Shapes of the Laravel API responses (see docs/api.md).

export type ReportStatus = 'pending' | 'in_progress' | 'completed' | 'rejected';

export type ReportCategory = 'organic' | 'plastic' | 'paper';

export type User = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: 'admin' | 'user';
  created_at: string;
};

export type StatusLog = {
  id: number;
  from_status: ReportStatus | null;
  from_status_label: string | null;
  to_status: ReportStatus;
  to_status_label: string;
  note: string | null;
  changed_by: string | null;
  created_at: string;
};

export type Report = {
  id: number;
  description: string;
  photo_url: string;
  latitude: number;
  longitude: number;
  address: string | null;
  category: ReportCategory | null;
  category_label: string | null;
  ai_label: ReportCategory | null;
  ai_confidence: number | null;
  status: ReportStatus;
  status_label: string;
  admin_note: string | null;
  status_logs?: StatusLog[];
  created_at: string;
  updated_at: string;
};

export type DashboardSummary = Record<ReportStatus, number> & { total: number };

export type Classification = {
  label: ReportCategory;
  confidence: number;
  probabilities: Record<ReportCategory, number>;
};

export type Paginated<T> = {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
};

export type AuthResult = { user: User; token: string };

export type RegisterInput = {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
};

export type NewReport = {
  photoUri: string;
  latitude: number;
  longitude: number;
  description: string;
  address?: string;
  category?: ReportCategory;
  aiLabel?: ReportCategory;
  aiConfidence?: number;
};
