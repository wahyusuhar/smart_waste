import type { Report } from '../api/types';
import { categoryLabels } from '../theme';

const dateTimeFormat =new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
});

// "2 Nov 2026, 13.52", the same style as the website.
export function formatDateTime(iso: string): string {
  return dateTimeFormat.format(new Date(iso));
}

// At most 6 decimals (about 10 cm) without trailing zeros, the same as the website.
export function formatCoordinate(value: number): string {
  return String(Number(value.toFixed(6)));
}

export function formatPercent(fraction: number): string {
  return `${(fraction * 100).toFixed(1).replace('.', ',')}%`;
}

export function firstName(fullName: string): string {
  return fullName.trim().split(/\s+/)[0] ?? fullName;
}

// "SW-0021", the same report number as the website's opening page.
export function reportCode(id: number): string {
  return `SW-${String(id).padStart(4, '0')}`;
}

// "Pasar Garung, Jl. Dieng, Wonosobo" becomes "Pasar Garung".
export function shortPlace(address: string | null): string | null {
  const place = address?.split(',')[0]?.trim();
  return place ? place : null;
}

// "Sampah Plastik di Pasar Garung", the card title used on the website.
export function reportTitle(report: Pick<Report, 'category' | 'address'>): string {
  const category = report.category ? categoryLabels[report.category] : null;
  const place = shortPlace(report.address);

  if (category && place) return `Sampah ${category} di ${place}`;
  if (category) return `Sampah ${category}`;
  if (place) return `Sampah di ${place}`;
  return 'Laporan sampah';
}
