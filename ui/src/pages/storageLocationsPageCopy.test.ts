import { describe, expect, it } from 'vitest';
import { WAREHOUSE_LOCATION_PAGE_HEADING } from './storageLocationsPageCopy';

describe('WAREHOUSE_LOCATION_PAGE_HEADING', () => {
  it('uses the warehouse location page title', () => {
    expect(WAREHOUSE_LOCATION_PAGE_HEADING).toBe('Warehouse Location');
  });
});
