import type { ReportCategory, ReportStatus } from './api/types';

/**
 * Same values as the website (web/resources/css/app.css and Tailwind 4's gray scale),
 * so the app and the web dashboard look like one product.
 */
export const colors = {
  accent: '#0f766e',
  accentPressed: '#115e59',
  accentSoft: '#f0fdfa',
  // Secondary text on the teal Beranda banner; readable on the accent color.
  accentTint: '#ccfbf1',

  white: '#ffffff',
  gray50: '#f9fafb',
  gray100: '#f3f4f6',
  gray200: '#e5e7eb',
  gray300: '#d1d5dc',
  gray400: '#99a1af',
  gray500: '#6a7282',
  gray600: '#4a5565',
  gray700: '#364153',
  gray900: '#101828',

  danger: '#c10007',
  dangerPressed: '#9f0712',
};

export const statusColors: Record<ReportStatus, { background: string; text: string; border: string }> = {
  pending: { background: '#fffbeb', text: '#92400e', border: '#fde68a' },
  in_progress: { background: '#eff6ff', text: '#1e40af', border: '#bfdbfe' },
  completed: { background: '#f0fdf4', text: '#166534', border: '#bbf7d0' },
  rejected: { background: '#fef2f2', text: '#991b1b', border: '#fecaca' },
};

export const statusLabels: Record<ReportStatus, string> = {
  pending: 'Menunggu',
  in_progress: 'Diproses',
  completed: 'Selesai',
  rejected: 'Ditolak',
};

export const categoryLabels: Record<ReportCategory, string> = {
  organic: 'Organik',
  plastic: 'Plastik',
  paper: 'Kertas',
};

export const radius = {
  control: 6,
  badge: 4,
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 24,
};

// React Native needs one font family name per weight.
export const fonts = {
  regular: 'Inter_400Regular',
  medium: 'Inter_500Medium',
  semibold: 'Inter_600SemiBold',
};

export const type = {
  title: { fontFamily: fonts.semibold, fontSize: 20, lineHeight: 28, color: colors.gray900 },
  heading: { fontFamily: fonts.semibold, fontSize: 16, lineHeight: 24, color: colors.gray900 },
  body: { fontFamily: fonts.regular, fontSize: 14, lineHeight: 20, color: colors.gray900 },
  label: { fontFamily: fonts.medium, fontSize: 14, lineHeight: 20, color: colors.gray700 },
  caption: { fontFamily: fonts.regular, fontSize: 13, lineHeight: 18, color: colors.gray500 },
} as const;
