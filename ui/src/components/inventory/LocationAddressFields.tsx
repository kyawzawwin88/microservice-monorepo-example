import React from 'react';
import { COUNTRIES } from '../../data/countries';
import { fieldInputClass, type LocationFieldErrors } from './locationFormUtils';
import type { LocationAddressValues } from './locationFormUtils';

export type { LocationAddressValues };

type Props = {
  values: LocationAddressValues;
  onChange: (values: LocationAddressValues) => void;
  idPrefix: string;
  fieldErrors?: LocationFieldErrors;
};

function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return <p className="mt-1 text-xs text-red-600">{message}</p>;
}

export default function LocationAddressFields({ values, onChange, idPrefix, fieldErrors }: Props) {
  const set = (patch: Partial<LocationAddressValues>) => onChange({ ...values, ...patch });

  return (
    <div className="grid gap-3 sm:grid-cols-2 sm:col-span-2">
      <div className="sm:col-span-2">
        <label htmlFor={`${idPrefix}-street`} className="block text-sm font-medium text-gray-700 mb-1">
          Street / locality
        </label>
        <textarea
          id={`${idPrefix}-street`}
          rows={3}
          value={values.address_street}
          onChange={(e) => set({ address_street: e.target.value })}
          className={fieldInputClass(!!fieldErrors?.address_street)}
          placeholder="Building, street, city (optional)"
          aria-invalid={!!fieldErrors?.address_street}
        />
        <FieldError message={fieldErrors?.address_street} />
      </div>
      <div>
        <label htmlFor={`${idPrefix}-country`} className="block text-sm font-medium text-gray-700 mb-1">
          Country
        </label>
        <select
          id={`${idPrefix}-country`}
          value={values.country_code}
          onChange={(e) => set({ country_code: e.target.value })}
          className={fieldInputClass(!!fieldErrors?.country_code)}
          aria-invalid={!!fieldErrors?.country_code}
        >
          <option value="">— Select country —</option>
          {COUNTRIES.map((c) => (
            <option key={c.code} value={c.code}>
              {c.name} ({c.code})
            </option>
          ))}
        </select>
        <FieldError message={fieldErrors?.country_code} />
      </div>
      <div>
        <label htmlFor={`${idPrefix}-postal`} className="block text-sm font-medium text-gray-700 mb-1">
          Postal code
        </label>
        <input
          id={`${idPrefix}-postal`}
          type="text"
          value={values.postal_code}
          onChange={(e) => set({ postal_code: e.target.value })}
          className={fieldInputClass(!!fieldErrors?.postal_code)}
          placeholder="e.g. 94103"
          aria-invalid={!!fieldErrors?.postal_code}
        />
        <FieldError message={fieldErrors?.postal_code} />
      </div>
    </div>
  );
}
