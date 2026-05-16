import { describe, expect, it } from 'vitest';

export function isLowStock(available: number, threshold = 5): boolean {
  return available <= threshold;
}

describe('stockReportUtils', () => {
  it('flags at or below threshold as low stock', () => {
    expect(isLowStock(5)).toBe(true);
    expect(isLowStock(6)).toBe(false);
  });
});
