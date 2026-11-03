import { reportCode, reportTitle, shortPlace } from './format';

describe('reportCode', () => {
  it('pads the id to four digits', () => {
    expect(reportCode(21)).toBe('SW-0021');
    expect(reportCode(12345)).toBe('SW-12345');
  });
});

describe('shortPlace', () => {
  it('keeps the part before the first comma', () => {
    expect(shortPlace('Pasar Garung, Jl. Dieng, Wonosobo')).toBe('Pasar Garung');
  });

  it('returns null without an address', () => {
    expect(shortPlace(null)).toBeNull();
    expect(shortPlace('  ')).toBeNull();
  });
});

describe('reportTitle', () => {
  it('names the category and the place', () => {
    expect(reportTitle({ category: 'plastic', address: 'Pasar Garung, Jl. Dieng' })).toBe('Sampah Plastik di Pasar Garung');
  });

  it('falls back when the category or the address is missing', () => {
    expect(reportTitle({ category: 'paper', address: null })).toBe('Sampah Kertas');
    expect(reportTitle({ category: null, address: 'Terminal Mendolo' })).toBe('Sampah di Terminal Mendolo');
    expect(reportTitle({ category: null, address: null })).toBe('Laporan sampah');
  });
});
