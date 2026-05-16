import { ApiRequestError } from '../../api/client';
import { countryName } from '../../data/countries';

export type LocationFieldKey = 'name' | 'code' | 'address_street' | 'country_code' | 'postal_code';
export type LocationFieldErrors = Partial<Record<LocationFieldKey, string>>;

export interface LocationFormFields {
  name: string;
  code: string;
}

export interface LocationFormValues extends LocationFormFields {
  address: LocationAddressValues;
}

export const emptyLocationFormValues = (): LocationFormValues => ({
  name: '',
  code: '',
  address: emptyLocationAddress(),
});

export function locationFormValuesFromLocation(loc: {
  name: string;
  code: string;
  address_street?: string | null;
  country_code?: string | null;
  postal_code?: string | null;
}): LocationFormValues {
  return {
    name: loc.name,
    code: loc.code,
    address: {
      address_street: loc.address_street ?? '',
      country_code: loc.country_code ?? '',
      postal_code: loc.postal_code ?? '',
    },
  };
}

export function locationPayloadFromForm(values: LocationFormValues) {
  const address = values.address;
  return {
    name: values.name.trim(),
    code: values.code.trim().toUpperCase(),
    address_street: address.address_street.trim() || undefined,
    country_code: address.country_code.trim() || undefined,
    postal_code: address.postal_code.trim() || undefined,
  };
}

export function validateFullLocationForm(values: LocationFormValues): string | null {
  return firstLocationFormErrorMessage(collectLocationFormErrors(values));
}

export function collectLocationFormErrors(values: LocationFormValues): LocationFieldErrors {
  const errors: LocationFieldErrors = {};
  const name = values.name.trim();
  const code = values.code.trim();

  if (!name) {
    errors.name = 'Name is required.';
  }
  if (!code) {
    errors.code = 'Code is required.';
  } else if (code.length > 50) {
    errors.code = 'Code must be 50 characters or fewer.';
  } else if (!/^[A-Za-z0-9_-]+$/.test(code)) {
    errors.code = 'Code may only contain letters, numbers, hyphens, and underscores.';
  }

  const street = values.address.address_street.trim();
  const country = values.address.country_code.trim().toUpperCase();
  const postal = values.address.postal_code.trim();

  if (street.length > STREET_MAX_LENGTH) {
    errors.address_street = `Street or locality must be ${STREET_MAX_LENGTH} characters or fewer.`;
  }
  if (postal && !POSTAL_PATTERN.test(postal)) {
    errors.postal_code =
      'Postal code must be 2–20 characters and contain only letters, numbers, spaces, and hyphens.';
  }
  if (postal && !country) {
    errors.country_code = 'Country is required when a postal code is provided.';
  }

  return errors;
}

export function hasLocationFieldErrors(errors: LocationFieldErrors): boolean {
  return Object.keys(errors).length > 0;
}

export function firstLocationFormErrorMessage(errors: LocationFieldErrors): string | null {
  const first = Object.values(errors)[0];
  return first ?? null;
}

export function locationFieldErrorsFromApi(err: unknown): LocationFieldErrors {
  if (!(err instanceof ApiRequestError) || !err.errors) {
    return {};
  }

  const allowed = new Set<LocationFieldKey>([
    'name',
    'code',
    'address_street',
    'country_code',
    'postal_code',
  ]);
  const mapped: LocationFieldErrors = {};

  for (const [key, messages] of Object.entries(err.errors)) {
    if (allowed.has(key as LocationFieldKey) && messages[0]) {
      mapped[key as LocationFieldKey] = messages[0];
    }
  }

  return mapped;
}

export function fieldInputClass(hasError: boolean, base = 'w-full rounded-lg border px-3 py-2 text-sm'): string {
  return hasError
    ? `${base} border-red-400 focus:border-red-500 focus:ring-red-500`
    : `${base} border-gray-300`;
}

export interface LocationAddressValues {
  address_street: string;
  country_code: string;
  postal_code: string;
}

export const emptyLocationAddress = (): LocationAddressValues => ({
  address_street: '',
  country_code: '',
  postal_code: '',
});

export const STREET_MAX_LENGTH = 500;
export const POSTAL_PATTERN = /^[A-Za-z0-9 -]{2,20}$/;

export function validateLocationForm(fields: LocationFormFields): string | null {
  const name = fields.name.trim();
  const code = fields.code.trim();

  if (!name) {
    return 'Name is required.';
  }
  if (!code) {
    return 'Code is required.';
  }
  if (code.length > 50) {
    return 'Code must be 50 characters or fewer.';
  }
  if (!/^[A-Za-z0-9_-]+$/.test(code)) {
    return 'Code may only contain letters, numbers, hyphens, and underscores.';
  }
  return null;
}

export function validateLocationAddress(fields: LocationAddressValues): string | null {
  const street = fields.address_street.trim();
  const country = fields.country_code.trim().toUpperCase();
  const postal = fields.postal_code.trim();

  if (street.length > STREET_MAX_LENGTH) {
    return `Street or locality must be ${STREET_MAX_LENGTH} characters or fewer.`;
  }

  if (postal && !POSTAL_PATTERN.test(postal)) {
    return 'Postal code must be 2–20 characters and contain only letters, numbers, spaces, and hyphens.';
  }

  if (postal && !country) {
    return 'Country is required when a postal code is provided.';
  }

  return null;
}

export function formatLocationAddressSummary(
  loc: {
    address_street?: string | null;
    country_code?: string | null;
    postal_code?: string | null;
  }
): string {
  const street = loc.address_street?.trim();
  const country = loc.country_code?.trim();
  const postal = loc.postal_code?.trim();

  if (!street && !country && !postal) {
    return 'No address on file';
  }

  const parts: string[] = [];
  if (street) {
    const short = street.length > 60 ? `${street.slice(0, 57)}…` : street;
    parts.push(short.replace(/\n/g, ', '));
  }
  if (country) {
    const label = countryName(country) ?? country;
    parts.push(postal ? `${label} ${postal}` : label);
  } else if (postal) {
    parts.push(`Postal: ${postal} (country missing)`);
  }

  const missing: string[] = [];
  if (street && !country) missing.push('country');
  if (street && !postal && country) {
    /* country without postal is allowed */
  }

  let summary = parts.join(' · ');
  if (missing.length > 0) {
    summary += ` (${missing.join(', ')} not set)`;
  }

  return summary;
}
