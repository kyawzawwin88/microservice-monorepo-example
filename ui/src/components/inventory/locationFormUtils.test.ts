import { describe, expect, it } from 'vitest';
import { ApiRequestError } from '../../api/client';
import {
  collectLocationFormErrors,
  emptyLocationFormValues,
  formatLocationAddressSummary,
  hasLocationFieldErrors,
  locationFieldErrorsFromApi,
  locationFormValuesFromLocation,
  locationPayloadFromForm,
  validateFullLocationForm,
  validateLocationAddress,
  validateLocationForm,
} from './locationFormUtils';

describe('validateLocationForm', () => {
  it('requires name and code', () => {
    expect(validateLocationForm({ name: '', code: '' })).toBe('Name is required.');
    expect(validateLocationForm({ name: 'Main', code: '' })).toBe('Code is required.');
  });

  it('rejects invalid code characters', () => {
    expect(validateLocationForm({ name: 'Main', code: 'wh main' })).toMatch(/letters/);
  });

  it('accepts valid input', () => {
    expect(validateLocationForm({ name: 'Main Warehouse', code: 'WH-MAIN' })).toBeNull();
  });
});

describe('validateLocationAddress', () => {
  it('requires country when postal provided', () => {
    expect(
      validateLocationAddress({ address_street: '', country_code: '', postal_code: '94103' })
    ).toMatch(/Country/);
  });

  it('accepts country without postal', () => {
    expect(
      validateLocationAddress({ address_street: 'Main', country_code: 'US', postal_code: '' })
    ).toBeNull();
  });
});

describe('locationFormValuesFromLocation', () => {
  it('maps storage location fields into form values', () => {
    const values = locationFormValuesFromLocation({
      name: 'Main',
      code: 'WH-MAIN',
      address_street: '123 St',
      country_code: 'US',
      postal_code: '94103',
    });
    expect(values.name).toBe('Main');
    expect(values.address.country_code).toBe('US');
  });
});

describe('locationPayloadFromForm', () => {
  it('trims and uppercases code', () => {
    const payload = locationPayloadFromForm({
      ...emptyLocationFormValues(),
      name: '  Main  ',
      code: ' wh-main ',
    });
    expect(payload.name).toBe('Main');
    expect(payload.code).toBe('WH-MAIN');
  });
});

describe('collectLocationFormErrors', () => {
  it('returns per-field errors', () => {
    const errors = collectLocationFormErrors(emptyLocationFormValues());
    expect(errors.name).toMatch(/Name/);
    expect(errors.code).toMatch(/Code/);
    expect(hasLocationFieldErrors(errors)).toBe(true);
  });
});

describe('locationFieldErrorsFromApi', () => {
  it('maps Laravel validation errors to form fields', () => {
    const err = new ApiRequestError('Validation failed', 422, {
      code: ['The code has already been taken.'],
    });
    expect(locationFieldErrorsFromApi(err).code).toMatch(/taken/);
  });
});

describe('validateFullLocationForm', () => {
  it('returns first validation error', () => {
    expect(validateFullLocationForm(emptyLocationFormValues())).toMatch(/Name/);
  });
});

describe('formatLocationAddressSummary', () => {
  it('shows no address message when empty', () => {
    expect(formatLocationAddressSummary({})).toBe('No address on file');
  });

  it('includes country and postal', () => {
    const summary = formatLocationAddressSummary({
      address_street: '123 Main',
      country_code: 'US',
      postal_code: '94103',
    });
    expect(summary).toContain('United States');
    expect(summary).toContain('94103');
  });
});
