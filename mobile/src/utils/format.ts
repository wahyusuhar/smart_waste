const dateTimeFormat = new Intl.DateTimeFormat('id-ID', {
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
